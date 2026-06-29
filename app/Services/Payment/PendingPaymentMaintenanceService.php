<?php

namespace App\Services\Payment;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentVerificationStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Service;
use App\Services\Support\AuditLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PendingPaymentMaintenanceService extends Service
{
    public function __construct(
        private readonly EsewaPaymentService $esewaPaymentService,
        private readonly PaymentFinalizationService $paymentFinalizationService,
        private readonly AuditLogService $auditLogService,
    ) {}

    public function cleanupStalePending(int $hours = 24): int
    {
        $cutoff = now()->subHours($hours);

        $payments = Payment::query()
            ->whereIn('status', [PaymentStatus::Initiated, PaymentStatus::PendingReview])
            ->whereNull('paid_at')
            ->where('created_at', '<=', $cutoff)
            ->get();

        $count = 0;

        foreach ($payments as $payment) {
            DB::transaction(function () use ($payment): void {
                $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
                $order = Order::query()->lockForUpdate()->findOrFail($payment->order_id);

                if ($payment->status === PaymentStatus::Paid) {
                    return;
                }

                $payment->update([
                    'status' => PaymentStatus::Failed,
                    'verification_status' => PaymentVerificationStatus::Failed,
                ]);

                $order->update([
                    'payment_status' => PaymentStatus::Failed,
                    'order_status' => OrderStatus::Failed,
                ]);

                $order->statusHistories()->create([
                    'status' => OrderStatus::Failed->value,
                    'message' => 'Pending payment expired during cleanup.',
                    'changed_by' => $order->user_id,
                    'created_at' => now(),
                ]);

                $this->auditLogService->record(null, 'payment.stale_cleanup', $payment, [
                    'order_number' => $order->order_number,
                ]);
            });

            $count++;
        }

        Log::info('payments.cleanup_stale_pending.completed', [
            'count' => $count,
            'hours' => $hours,
        ]);

        return $count;
    }

    public function reconcilePending(int $hours = 1, int $limit = 25): array
    {
        $cutoff = now()->subHours($hours);

        $payments = Payment::query()
            ->whereIn('status', [PaymentStatus::Initiated, PaymentStatus::PendingReview])
            ->where('created_at', '<=', $cutoff)
            ->latest('id')
            ->limit($limit)
            ->get();

        $processed = 0;
        $resolved = 0;
        $failed = 0;

        foreach ($payments as $payment) {
            try {
                $verification = $this->esewaPaymentService->verifyExistingPayment($payment);
                $result = $this->paymentFinalizationService->finalize($payment, $verification);
                $processed++;

                if (($result['payment']->status->value ?? null) === PaymentStatus::Paid->value) {
                    $resolved++;
                }
            } catch (\Throwable $exception) {
                $failed++;

                Log::warning('payments.reconcile_pending.failed', [
                    'payment_id' => $payment->id,
                    'transaction_uuid' => $payment->transaction_uuid,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return [
            'processed' => $processed,
            'resolved' => $resolved,
            'failed' => $failed,
        ];
    }
}
