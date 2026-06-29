<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Checkout\PrepareCheckoutRequest;
use App\Http\Resources\Checkout\CheckoutPreparationResource;
use App\Models\Order;
use App\Services\Checkout\CheckoutPreparationService;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CheckoutPreparationService $checkoutPreparationService,
    ) {}

    public function store(PrepareCheckoutRequest $request): CheckoutPreparationResource
    {
        $this->authorize('create', Order::class);

        return new CheckoutPreparationResource(
            $this->checkoutPreparationService->prepare($request->user(), $request->validated())
        );
    }
}
