<?php

namespace App\Services\Payment;

use App\Enums\PaymentStatus;
use App\Enums\PaymentVerificationStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\Service;
use App\Services\Support\AuditLogService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EsewaPaymentService extends Service
{
    public function __construct(
        private readonly HttpFactory $http,
        private readonly AuditLogService $auditLogService,
    ) {}

    public function initiate(Order $order, ?User $actor = null): array
    {
        $payment = $this->resolveInitiatablePayment($order);

        if ($payment->status === PaymentStatus::Paid) {
            throw ValidationException::withMessages([
                'order_number' => ['This order is already paid.'],
            ]);
        }

        $payload = $this->buildInitiationPayload($order, $payment);

        $payment->update([
            'raw_request_json' => $this->mergeAttempts(
                $payment->raw_request_json,
                'initiate',
                $payload
            ),
        ]);

        $this->auditLogService->record($actor, 'payment.initiated', $payment, [
            'order_number' => $order->order_number,
            'transaction_uuid' => $payment->transaction_uuid,
        ]);

        return [
            'order' => $order,
            'payment' => $payment->fresh(),
            'payload' => $payload,
        ];
    }

    public function resolvePaymentFromCallback(Request $request): Payment
    {
        $callbackPayload = $this->decodeCallbackPayload((string) $request->query('data', ''));
        $transactionUuid = (string) ($callbackPayload['transaction_uuid'] ?? $request->query('transaction_uuid', ''));
        $orderNumber = (string) $request->query('order_number', '');

        if ($transactionUuid !== '') {
            $payment = Payment::query()
                ->with('order')
                ->where('transaction_uuid', $transactionUuid)
                ->first();

            if ($payment) {
                return $payment;
            }
        }

        if ($orderNumber !== '') {
            return Payment::query()
                ->with('order')
                ->whereHas('order', fn ($query) => $query->where('order_number', $orderNumber))
                ->latest('id')
                ->firstOrFail();
        }

        throw ValidationException::withMessages([
            'payment' => ['Unable to resolve the payment from the callback payload.'],
        ]);
    }

    public function verifySuccessfulCallback(Payment $payment, Request $request): array
    {
        return $this->verifyPayment(
            $payment,
            $this->decodeCallbackPayload((string) $request->query('data', '')),
            true
        );
    }

    public function verifyFailedCallback(Payment $payment, Request $request): array
    {
        return $this->verifyPayment(
            $payment,
            $this->decodeCallbackPayload((string) $request->query('data', '')),
            false
        );
    }

    public function verifyExistingPayment(Payment $payment, array $attributes = []): array
    {
        if (($attributes['transaction_uuid'] ?? null) !== null && $attributes['transaction_uuid'] !== $payment->transaction_uuid) {
            throw ValidationException::withMessages([
                'transaction_uuid' => ['The transaction UUID does not match the stored payment attempt.'],
            ]);
        }

        return $this->verifyPayment($payment, [], null);
    }

    public function buildInitiationPayload(Order $order, Payment $payment): array
    {
        $amount = max((float) $order->grand_total - (float) $order->tax_total - (float) $order->shipping_total, 0);
        $taxAmount = (float) $order->tax_total;
        $deliveryCharge = (float) $order->shipping_total;
        $serviceCharge = 0.0;
        $totalAmount = round($amount + $taxAmount + $deliveryCharge + $serviceCharge, 2);
        $signedFieldNames = 'total_amount,transaction_uuid,product_code';

        $payload = [
            'gateway' => 'esewa',
            'form_url' => rtrim((string) config('services.esewa.form_url'), '/'),
            'method' => 'POST',
            'fields' => [
                'amount' => $this->formatAmount($amount),
                'tax_amount' => $this->formatAmount($taxAmount),
                'total_amount' => $this->formatAmount($totalAmount),
                'transaction_uuid' => $payment->transaction_uuid,
                'product_code' => (string) config('services.esewa.merchant_code'),
                'product_service_charge' => $this->formatAmount($serviceCharge),
                'product_delivery_charge' => $this->formatAmount($deliveryCharge),
                'success_url' => $this->callbackUrl((string) config('services.esewa.success_url'), $order->order_number),
                'failure_url' => $this->callbackUrl((string) config('services.esewa.failure_url'), $order->order_number),
                'signed_field_names' => $signedFieldNames,
            ],
        ];

        $payload['fields']['signature'] = $this->generateSignature(
            $payload['fields'],
            $signedFieldNames
        );

        return $payload;
    }

    private function verifyPayment(Payment $payment, array $callbackPayload, ?bool $expectsSuccess): array
    {
        $order = $payment->order()->firstOrFail();
        $localPayload = $this->buildInitiationPayload($order, $payment);
        $callbackVerified = $callbackPayload === []
            ? null
            : $this->verifySignature($callbackPayload, (string) ($callbackPayload['signed_field_names'] ?? ''));

        if ($callbackPayload !== []) {
            if (($callbackPayload['transaction_uuid'] ?? null) !== $payment->transaction_uuid) {
                return $this->failedVerification(
                    'mismatch',
                    'Callback transaction UUID does not match the stored payment.',
                    $callbackPayload,
                    []
                );
            }

            if (($callbackPayload['product_code'] ?? null) !== config('services.esewa.merchant_code')) {
                return $this->failedVerification(
                    'mismatch',
                    'Callback product code does not match the configured merchant code.',
                    $callbackPayload,
                    []
                );
            }

            if ($this->formatAmount((float) ($callbackPayload['total_amount'] ?? 0)) !== $localPayload['fields']['total_amount']) {
                return $this->failedVerification(
                    'mismatch',
                    'Callback total amount does not match the stored payment amount.',
                    $callbackPayload,
                    []
                );
            }
        }

        $statusResponse = $this->statusCheck($payment, $localPayload['fields']['total_amount']);
        $status = strtoupper((string) ($statusResponse['status'] ?? 'NOT_FOUND'));
        $gatewayReference = (string) ($statusResponse['refId'] ?? $statusResponse['transaction_code'] ?? $callbackPayload['transaction_code'] ?? '');
        $responseAmount = $this->formatAmount((float) ($statusResponse['totalAmount'] ?? $statusResponse['total_amount'] ?? 0));

        if (! in_array($status, ['SERVICE_UNAVAILABLE', 'UNKNOWN'], true) && $responseAmount !== $localPayload['fields']['total_amount']) {
            return $this->failedVerification(
                'mismatch',
                'Gateway verification amount does not match the stored payment amount.',
                $callbackPayload,
                $statusResponse,
                $gatewayReference
            );
        }

        if (($expectsSuccess === true) && $callbackVerified === false) {
            Log::warning('esewa.success_callback_signature_invalid', [
                'payment_id' => $payment->id,
                'transaction_uuid' => $payment->transaction_uuid,
            ]);
        }

        return match ($status) {
            'COMPLETE' => [
                'status' => 'paid',
                'verified' => true,
                'reason' => $callbackVerified === false
                    ? 'Redirect signature was invalid, but backend status verification completed successfully.'
                    : 'Payment verified successfully with eSewa.',
                'payment_status' => PaymentStatus::Paid,
                'verification_status' => PaymentVerificationStatus::Verified,
                'gateway_reference' => $gatewayReference !== '' ? $gatewayReference : null,
                'callback_payload' => $callbackPayload,
                'gateway_response' => $statusResponse,
            ],
            'PENDING', 'AMBIGIOUS', 'AMBIGUOUS' => [
                'status' => 'pending_review',
                'verified' => false,
                'reason' => 'Payment is not finalized yet and remains pending review.',
                'payment_status' => PaymentStatus::PendingReview,
                'verification_status' => PaymentVerificationStatus::Pending,
                'gateway_reference' => $gatewayReference !== '' ? $gatewayReference : null,
                'callback_payload' => $callbackPayload,
                'gateway_response' => $statusResponse,
            ],
            'CANCELED' => [
                'status' => 'cancelled',
                'verified' => false,
                'reason' => 'Payment was cancelled at eSewa.',
                'payment_status' => PaymentStatus::Cancelled,
                'verification_status' => PaymentVerificationStatus::Failed,
                'gateway_reference' => $gatewayReference !== '' ? $gatewayReference : null,
                'callback_payload' => $callbackPayload,
                'gateway_response' => $statusResponse,
            ],
            'SERVICE_UNAVAILABLE', 'UNKNOWN' => [
                'status' => 'pending_review',
                'verified' => false,
                'reason' => 'Gateway verification is temporarily unavailable. Please retry verification.',
                'payment_status' => PaymentStatus::PendingReview,
                'verification_status' => PaymentVerificationStatus::Pending,
                'gateway_reference' => $gatewayReference !== '' ? $gatewayReference : null,
                'callback_payload' => $callbackPayload,
                'gateway_response' => $statusResponse,
            ],
            default => $this->failedVerification(
                'failed',
                'Payment verification did not complete successfully.',
                $callbackPayload,
                $statusResponse,
                $gatewayReference !== '' ? $gatewayReference : null
            ),
        };
    }

    private function resolveInitiatablePayment(Order $order): Payment
    {
        $latestPayment = $order->payments()
            ->latest('id')
            ->firstOrFail();

        if (in_array($latestPayment->status, [PaymentStatus::Failed, PaymentStatus::Cancelled], true)) {
            return Payment::query()->create([
                'order_id' => $order->id,
                'payment_method' => 'esewa',
                'gateway' => 'esewa',
                'amount' => $latestPayment->amount,
                'transaction_uuid' => (string) Str::uuid(),
                'status' => PaymentStatus::Initiated,
                'verification_status' => PaymentVerificationStatus::Pending,
            ]);
        }

        return $latestPayment;
    }

    private function statusCheck(Payment $payment, string $totalAmount): array
    {
        try {
            $response = $this->http
                ->acceptJson()
                ->connectTimeout(5)
                ->timeout(10)
                ->retry(2, 200)
                ->get((string) config('services.esewa.status_check_url'), [
                    'product_code' => (string) config('services.esewa.merchant_code'),
                    'total_amount' => $totalAmount,
                    'transaction_uuid' => $payment->transaction_uuid,
                ]);

            return $response->json() ?? [];
        } catch (ConnectionException $exception) {
            Log::warning('esewa.status_check.connection_failed', [
                'payment_id' => $payment->id,
                'transaction_uuid' => $payment->transaction_uuid,
                'message' => $exception->getMessage(),
            ]);

            return [
                'status' => 'SERVICE_UNAVAILABLE',
                'error' => 'Unable to connect to eSewa status check endpoint.',
            ];
        }
    }

    private function failedVerification(
        string $status,
        string $reason,
        array $callbackPayload,
        array $statusResponse,
        ?string $gatewayReference = null,
    ): array {
        return [
            'status' => $status,
            'verified' => false,
            'reason' => $reason,
            'payment_status' => PaymentStatus::Failed,
            'verification_status' => $status === 'mismatch'
                ? PaymentVerificationStatus::Mismatch
                : PaymentVerificationStatus::Failed,
            'gateway_reference' => $gatewayReference,
            'callback_payload' => $callbackPayload,
            'gateway_response' => $statusResponse,
        ];
    }

    private function callbackUrl(string $baseUrl, string $orderNumber): string
    {
        $separator = str_contains($baseUrl, '?') ? '&' : '?';

        return $baseUrl.$separator.'order_number='.urlencode($orderNumber);
    }

    private function decodeCallbackPayload(string $encodedData): array
    {
        if ($encodedData === '') {
            return [];
        }

        $decoded = base64_decode(str_replace(' ', '+', $encodedData), true);

        if ($decoded === false) {
            throw ValidationException::withMessages([
                'data' => ['The callback payload is not valid Base64 data.'],
            ]);
        }

        $payload = json_decode($decoded, true);

        if (! is_array($payload)) {
            throw ValidationException::withMessages([
                'data' => ['The callback payload is not valid JSON.'],
            ]);
        }

        return $payload;
    }

    private function verifySignature(array $payload, string $signedFieldNames): bool
    {
        if ($signedFieldNames === '' || ! isset($payload['signature'])) {
            return false;
        }

        return hash_equals(
            (string) $payload['signature'],
            $this->generateSignature($payload, $signedFieldNames)
        );
    }

    private function generateSignature(array $payload, string $signedFieldNames): string
    {
        $message = collect(explode(',', $signedFieldNames))
            ->map(fn (string $field): string => trim($field))
            ->filter()
            ->map(fn (string $field): string => $field.'='.(string) Arr::get($payload, $field, ''))
            ->implode(',');

        return base64_encode(hash_hmac(
            'sha256',
            $message,
            (string) config('services.esewa.secret_key'),
            true
        ));
    }

    private function mergeAttempts(?array $existingPayload, string $type, array $payload): array
    {
        $existingPayload ??= [];
        $existingPayload['latest'] = $payload;
        $existingPayload['attempts'] = [
            ...($existingPayload['attempts'] ?? []),
            [
                'type' => $type,
                'payload' => $payload,
                'recorded_at' => now()->toIso8601String(),
            ],
        ];

        return $existingPayload;
    }

    private function formatAmount(float|int|string $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }
}
