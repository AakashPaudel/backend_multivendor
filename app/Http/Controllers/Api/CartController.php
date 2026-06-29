<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\StoreCartItemRequest;
use App\Http\Requests\Cart\UpdateCartItemRequest;
use App\Http\Resources\Cart\CartResource;
use App\Models\CartItem;
use App\Services\Cart\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
    ) {}

    public function show(Request $request): CartResource
    {
        $this->authorize('viewAny', CartItem::class);

        return new CartResource($this->cartService->show($request->user()));
    }

    public function store(StoreCartItemRequest $request): CartResource
    {
        $this->authorize('create', CartItem::class);

        return new CartResource(
            $this->cartService->addItem($request->user(), $request->validated())
        );
    }

    public function update(UpdateCartItemRequest $request, CartItem $cartItem): CartResource
    {
        $cartItem->loadMissing('cart');
        $this->authorize('update', $cartItem);

        return new CartResource(
            $this->cartService->updateItem($request->user(), $cartItem, $request->validated())
        );
    }

    public function destroy(Request $request, CartItem $cartItem): JsonResponse
    {
        $cartItem->loadMissing('cart');
        $this->authorize('delete', $cartItem);

        return response()->json(
            (new CartResource($this->cartService->deleteItem($cartItem)))->resolve($request)
        );
    }
}
