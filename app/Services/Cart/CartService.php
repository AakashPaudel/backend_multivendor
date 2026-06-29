<?php

namespace App\Services\Cart;

use App\Actions\Cart\GetOrCreateCartAction;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use App\Services\Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartService extends Service
{
    public function __construct(
        private readonly GetOrCreateCartAction $getOrCreateCartAction,
        private readonly CartTotalsService $cartTotalsService,
    ) {}

    public function show(User $user): array
    {
        return $this->cartTotalsService->summarize(
            $this->getOrCreateCartAction->handle($user)
        );
    }

    public function addItem(User $user, array $attributes): array
    {
        return DB::transaction(function () use ($user, $attributes): array {
            $cart = Cart::query()->lockForUpdate()->firstOrCreate([
                'user_id' => $user->id,
            ]);

            $product = Product::query()
                ->with(['vendor.vendorProfile'])
                ->findOrFail($attributes['product_id']);

            $quantity = (int) $attributes['quantity'];

            $existingItem = CartItem::query()
                ->whereBelongsTo($cart)
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->first();

            $desiredQuantity = $quantity + ($existingItem?->quantity ?? 0);

            $this->ensureProductCanBePurchased($product, $desiredQuantity);

            if ($existingItem) {
                $existingItem->update([
                    'quantity' => $desiredQuantity,
                    'vendor_id' => $product->vendor_id,
                    'unit_price' => $product->currentUnitPrice(),
                ]);
            } else {
                $cart->items()->create([
                    'product_id' => $product->id,
                    'vendor_id' => $product->vendor_id,
                    'quantity' => $quantity,
                    'unit_price' => $product->currentUnitPrice(),
                ]);
            }

            return $this->cartTotalsService->summarize($cart->fresh());
        });
    }

    public function updateItem(User $user, CartItem $cartItem, array $attributes): array
    {
        return DB::transaction(function () use ($cartItem, $attributes): array {
            $product = Product::query()
                ->with(['vendor.vendorProfile'])
                ->findOrFail($cartItem->product_id);

            $quantity = (int) $attributes['quantity'];

            $this->ensureProductCanBePurchased($product, $quantity);

            $cartItem->update([
                'quantity' => $quantity,
                'vendor_id' => $product->vendor_id,
                'unit_price' => $product->currentUnitPrice(),
            ]);

            return $this->cartTotalsService->summarize($cartItem->cart->fresh());
        });
    }

    public function deleteItem(CartItem $cartItem): array
    {
        return DB::transaction(function () use ($cartItem): array {
            $cart = $cartItem->cart;

            $cartItem->delete();

            return $this->cartTotalsService->summarize($cart->fresh());
        });
    }

    private function ensureProductCanBePurchased(Product $product, int $quantity): void
    {
        if (! $product->isSellable()) {
            throw ValidationException::withMessages([
                'product_id' => ['The selected product is not currently available for purchase.'],
            ]);
        }

        if ($quantity > $product->stock_quantity) {
            throw ValidationException::withMessages([
                'quantity' => ['The requested quantity exceeds available stock.'],
            ]);
        }
    }
}
