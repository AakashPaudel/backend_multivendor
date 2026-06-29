<?php

namespace App\Services\Payment;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentVerificationStatus;
use App\Exceptions\InsufficientStockException;
use App\Jobs\GenerateRecommendationsJob;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\User;
use App\Services\Order\InventoryService;
use App\Services\Service;
use App\Services\Support\AuditLogService;
use App\Services\Support\MarketplaceNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentFinalizationService extends Service
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly InventoryService $inventoryService,
        private readonly MarketplaceNotificationService $marketplaceNotificationService,
    ) {}

    public function finalize(Payment $payment, array $verification, ?User $actor = null): array
    {
        return DB::transaction(function () use ($payment, $verification, $actor): array {
            $payment = Payment::query()
                ->with('order')
                ->lockForUpdate()
                ->findOrFail($payment->id);

            $order = Order::query()->lockForUpdate()->findOrFail($payment->order_id);

            $payment->update([
                'gateway_reference' => $verification['gateway_reference'] ?? $payment->gateway_reference,
                'raw_response_json' => $this->mergeResponses($payment, $verification),
            ]);

            if ($verification['status'] === 'paid' && $payment->status === PaymentStatus::Paid) {
                $this->auditDuplicate($actor, $payment, $verification, 'payment.duplicate_success_callback');

                return $this->statusPayload(
                    $order->fresh(['payments' => fn ($query) => $query->latest('id')]),
                    $verification,
                    true,
                    'Payment was already finalized.'
                );
            }

            if (in_array($verification['status'], ['failed', 'cancelled', 'mismatch'], true)
                && in_array($payment->status, [PaymentStatus::Failed, PaymentStatus::Cancelled], true)) {
                $this->auditDuplicate($actor, $payment, $verification, 'payment.duplicate_terminal_callback');

                return $this->statusPayload(
                    $order->fresh(['payments' => fn ($query) => $query->latest('id')]),
                    $verification,
                    true,
                    'Payment was already finalized.'
                );
            }

            if ($payment->status === PaymentStatus::Paid && $verification['status'] !== 'paid') {
                $this->auditDuplicate($actor, $payment, $verification, 'payment.duplicate_non_terminal_callback');

                return $this->statusPayload(
                    $order->fresh(['payments' => fn ($query) => $query->latest('id')]),
                    $verification,
                    true,
                    'Payment was already finalized.'
                );
            }

            try {
                match ($verification['status']) {
                    'paid' => $this->markPaid($payment, $order, $verification, $actor),
                    'cancelled' => $this->markCancelled($payment, $order, $verification, $actor),
                    'pending_review' => $this->markPendingReview($payment, $order, $verification, $actor),
                    default => $this->markFailed($payment, $order, $verification, $actor),
                };
            } catch (InsufficientStockException $exception) {
                $inventoryReview = [
                    'status' => 'pending_review',
                    'verified' => true,
                    'reason' => $exception->getMessage(),
                    'payment_status' => PaymentStatus::PendingReview,
                    'verification_status' => PaymentVerificationStatus::Verified,
                    'gateway_reference' => $verification['gateway_reference'] ?? $payment->gateway_reference,
                ];

                $this->markPendingReview($payment, $order, $inventoryReview, $actor);

                return $this->statusPayload(
                    $order->fresh(['payments' => fn ($query) => $query->latest('id')]),
                    $inventoryReview
                );
            }

            return $this->statusPayload(
                $order->fresh(['payments' => fn ($query) => $query->latest('id')]),
                $verification
            );
        });
    }

    public function statusPayload(Order $order, ?array $verification = null, bool $idempotent = false, ?string $message = null): array
    {
        $payment = $order->payments->firstOrFail();

        return [
            'message' => $message ?? $this->messageForStatus($payment->status),
            'idempotent' => $idempotent,
            'verification' => [
                'status' => $verification['status'] ?? $payment->verification_status->value,
                'verified' => $verification['verified'] ?? ($payment->verification_status->value === 'verified'),
                'reason' => $verification['reason'] ?? $this->reasonForStatus($payment->status),
            ],
            'order' => $order,
            'payment' => $payment,
        ];
    }

    private function markPaid(Payment $payment, Order $order, array $verification, ?User $actor): void
    {
        $this->inventoryService->deductStockForOrder($order);

        $payment->update([
            'status' => PaymentStatus::Paid,
            'verification_status' => $verification['verification_status'],
            'gateway_reference' => $verification['gateway_reference'] ?? $payment->gateway_reference,
            'paid_at' => $payment->paid_at ?? now(),
        ]);

        $order->update([
            'payment_status' => PaymentStatus::Paid,
            'order_status' => OrderStatus::Paid,
        ]);

        $this->statusHistoryOnce($order, OrderStatus::Paid->value, 'Payment verified via eSewa.', $actor?->id ?? $order->user_id);
        $this->auditLogService->record($actor, 'payment.verified', $payment, [
            'order_number' => $order->order_number,
            'gateway_reference' => $payment->gateway_reference,
            'transaction_uuid' => $payment->transaction_uuid,
        ]);

        Log::info('payment.verified', [
            'payment_id' => $payment->id,
            'order_number' => $order->order_number,
            'transaction_uuid' => $payment->transaction_uuid,
        ]);

        $this->marketplaceNotificationService->sendPaymentStatus(
            $order->loadMissing('user', 'vendorOrders.vendor'),
            $payment,
            'paid',
            'Your payment has been verified successfully.'
        );

        GenerateRecommendationsJob::dispatch();
    }

    private function markCancelled(Payment $payment, Order $order, array $verification, ?User $actor): void
    {
        $payment->update([
            'status' => PaymentStatus::Cancelled,
            'verification_status' => $verification['verification_status'],
            'gateway_reference' => $verification['gateway_reference'] ?? $payment->gateway_reference,
        ]);

        $order->update([
            'payment_status' => PaymentStatus::Cancelled,
            'order_status' => OrderStatus::Cancelled,
        ]);

        $this->statusHistoryOnce($order, OrderStatus::Cancelled->value, 'Payment was cancelled at eSewa.', $actor?->id ?? $order->user_id);
        $this->auditLogService->record($actor, 'payment.cancelled', $payment, [
            'order_number' => $order->order_number,
            'transaction_uuid' => $payment->transaction_uuid,
        ]);

        Log::warning('payment.cancelled', [
            'payment_id' => $payment->id,
            'order_number' => $order->order_number,
            'transaction_uuid' => $payment->transaction_uuid,
        ]);

        $this->marketplaceNotificationService->sendPaymentStatus(
            $order->loadMissing('user', 'vendorOrders.vendor'),
            $payment,
            'cancelled',
            'Your payment was cancelled.'
        );
    }

    private function markPendingReview(Payment $payment, Order $order, array $verification, ?User $actor): void
    {
        $payment->update([
            'status' => PaymentStatus::PendingReview,
            'verification_status' => $verification['verification_status'],
            'gateway_reference' => $verification['gateway_reference'] ?? $payment->gateway_reference,
        ]);

        $order->update([
            'payment_status' => PaymentStatus::PendingReview,
            'order_status' => OrderStatus::PaymentInitiated,
        ]);

        $this->statusHistoryOnce($order, OrderStatus::PaymentInitiated->value, 'Payment verification is pending review.', $actor?->id ?? $order->user_id);
        $this->auditLogService->record($actor, 'payment.pending_review', $payment, [
            'order_number' => $order->order_number,
            'transaction_uuid' => $payment->transaction_uuid,
        ]);

        Log::warning('payment.pending_review', [
            'payment_id' => $payment->id,
            'order_number' => $order->order_number,
            'transaction_uuid' => $payment->transaction_uuid,
        ]);

        $this->marketplaceNotificationService->sendPaymentStatus(
            $order->loadMissing('user', 'vendorOrders.vendor'),
            $payment,
            'pending_review',
            'Your payment is pending review.'
        );
    }

    private function markFailed(Payment $payment, Order $order, array $verification, ?User $actor): void
    {
        $payment->update([
            'status' => PaymentStatus::Failed,
            'verification_status' => $verification['verification_status'],
            'gateway_reference' => $verification['gateway_reference'] ?? $payment->gateway_reference,
        ]);

        $order->update([
            'payment_status' => PaymentStatus::Failed,
            'order_status' => OrderStatus::Failed,
        ]);

        $this->statusHistoryOnce($order, OrderStatus::Failed->value, $verification['reason'], $actor?->id ?? $order->user_id);
        $this->auditLogService->record($actor, 'payment.failed', $payment, [
            'order_number' => $order->order_number,
            'transaction_uuid' => $payment->transaction_uuid,
            'reason' => $verification['reason'],
        ]);

        Log::error('payment.failed', [
            'payment_id' => $payment->id,
            'order_number' => $order->order_number,
            'transaction_uuid' => $payment->transaction_uuid,
            'reason' => $verification['reason'],
        ]);

        $this->marketplaceNotificationService->sendPaymentStatus(
            $order->loadMissing('user', 'vendorOrders.vendor'),
            $payment,
            'failed',
            'Your payment could not be verified successfully.'
        );
    }

    private function mergeResponses(Payment $payment, array $verification): array
    {
        $rawResponse = $payment->raw_response_json ?? [];
        $rawResponse['latest'] = [
            'verification' => $verification,
            'recorded_at' => now()->toIso8601String(),
        ];
        $rawResponse['events'] = [
            ...($rawResponse['events'] ?? []),
            [
                'verification' => $verification,
                'recorded_at' => now()->toIso8601String(),
            ],
        ];

        return $rawResponse;
    }

    private function statusHistoryOnce(Order $order, string $status, string $message, ?int $changedBy): void
    {
        $exists = OrderStatusHistory::query()
            ->where('order_id', $order->id)
            ->where('status', $status)
            ->where('message', $message)
            ->exists();

        if (! $exists) {
            $order->statusHistories()->create([
                'status' => $status,
                'message' => $message,
                'changed_by' => $changedBy,
                'created_at' => now(),
            ]);
        }
    }

    private function auditDuplicate(?User $actor, Payment $payment, array $verification, string $action): void
    {
        $this->auditLogService->record($actor, $action, $payment, [
            'transaction_uuid' => $payment->transaction_uuid,
            'status' => $verification['status'],
        ]);

        Log::info($action, [
            'payment_id' => $payment->id,
            'transaction_uuid' => $payment->transaction_uuid,
            'status' => $verification['status'],
        ]);
    }

    private function messageForStatus(PaymentStatus $status): string
    {
        return match ($status) {
            PaymentStatus::Paid => 'Payment has been verified successfully.',
            PaymentStatus::Cancelled => 'Payment was cancelled.',
            PaymentStatus::Failed => 'Payment verification failed.',
            PaymentStatus::PendingReview => 'Payment is pending review.',
            default => 'Payment is awaiting verification.',
        };
    }

    private function reasonForStatus(PaymentStatus $status): string
    {
        return match ($status) {
            PaymentStatus::Paid => 'Payment is verified.',
            PaymentStatus::Cancelled => 'Payment was cancelled at the gateway.',
            PaymentStatus::Failed => 'Payment did not verify successfully.',
            PaymentStatus::PendingReview => 'Gateway returned a non-terminal result.',
            default => 'Payment is still awaiting verification.',
        };
    }
}
