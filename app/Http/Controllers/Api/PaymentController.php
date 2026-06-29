<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\InitiateEsewaPaymentRequest;
use App\Http\Requests\Payment\VerifyEsewaPaymentRequest;
use App\Http\Resources\Payment\PaymentInitiationResource;
use App\Http\Resources\Payment\PaymentStatusResource;
use App\Models\Order;
use App\Services\Payment\EsewaPaymentService;
use App\Services\Payment\PaymentFinalizationService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        private readonly EsewaPaymentService $esewaPaymentService,
        private readonly PaymentFinalizationService $paymentFinalizationService,
    ) {}

    public function initiateEsewa(InitiateEsewaPaymentRequest $request): PaymentInitiationResource
    {
        $order = Order::query()
            ->with(['payments' => fn ($query) => $query->latest('id')])
            ->where('order_number', $request->validated('order_number'))
            ->firstOrFail();

        $this->authorize('view', $order);

        return new PaymentInitiationResource(
            $this->esewaPaymentService->initiate($order, $request->user())
        );
    }

    public function handleEsewaSuccess(Request $request): PaymentStatusResource
    {
        $payment = $this->esewaPaymentService->resolvePaymentFromCallback($request);
        $verification = $this->esewaPaymentService->verifySuccessfulCallback($payment, $request);

        return new PaymentStatusResource(
            $this->paymentFinalizationService->finalize($payment, $verification)
        );
    }

    public function handleEsewaFailure(Request $request): PaymentStatusResource
    {
        $payment = $this->esewaPaymentService->resolvePaymentFromCallback($request);
        $verification = $this->esewaPaymentService->verifyFailedCallback($payment, $request);

        return new PaymentStatusResource(
            $this->paymentFinalizationService->finalize($payment, $verification)
        );
    }

    public function verifyEsewa(VerifyEsewaPaymentRequest $request): PaymentStatusResource
    {
        $order = Order::query()
            ->with(['payments' => fn ($query) => $query->latest('id')])
            ->where('order_number', $request->validated('order_number'))
            ->firstOrFail();

        $this->authorize('view', $order);

        $payment = $order->payments->firstOrFail();
        $verification = $this->esewaPaymentService->verifyExistingPayment($payment, $request->validated());

        return new PaymentStatusResource(
            $this->paymentFinalizationService->finalize($payment, $verification, $request->user())
        );
    }

    public function showStatus(Order $order): PaymentStatusResource
    {
        $this->authorize('view', $order);

        return new PaymentStatusResource(
            $this->paymentFinalizationService->statusPayload(
                $order->load(['payments' => fn ($query) => $query->latest('id')])
            )
        );
    }
}
