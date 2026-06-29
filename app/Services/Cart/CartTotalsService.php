<?php

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use App\Services\Service;

class CartTotalsService extends Service
{
    public function summarize(Cart $cart): array
    {
        $cart->loadMissing([
            'items.product.category',
            'items.product.vendor.vendorProfile',
            'items.vendor.vendorProfile',
        ]);

        $items = $cart->items
            ->map(fn (CartItem $item): array => $this->summarizeItem($item))
            ->values();

        $subtotal = $items->sum('subtotal');
        $discountTotal = $items->sum('discount_total');
        $grandTotal = $items->sum('line_total');

        return [
            'cart' => $cart,
            'items' => $items->all(),
            'totals' => [
                'item_count' => $items->sum('quantity'),
                'distinct_items' => $items->count(),
                'vendor_count' => $items->pluck('vendor_id')->filter()->unique()->count(),
                'subtotal' => $this->formatAmount($subtotal),
                'discount_total' => $this->formatAmount($discountTotal),
                'shipping_total' => $this->formatAmount(0),
                'tax_total' => $this->formatAmount(0),
                'grand_total' => $this->formatAmount($grandTotal),
                'is_checkout_ready' => $items->isNotEmpty() && $items->every('is_available'),
            ],
        ];
    }

    private function summarizeItem(CartItem $cartItem): array
    {
        $product = $cartItem->product;
        $baseUnitPrice = $product ? (float) $product->price : (float) $cartItem->unit_price;
        $currentUnitPrice = $product ? $product->currentUnitPrice() : (float) $cartItem->unit_price;
        $quantity = $cartItem->quantity;
        $messages = [];
        $isAvailable = true;

        if (! $product) {
            $isAvailable = false;
            $messages[] = 'The product is no longer available.';
        } else {
            if (! $product->isSellable()) {
                $isAvailable = false;
                $messages[] = 'This product is not currently available for purchase.';
            }

            if ($quantity > $product->stock_quantity) {
                $isAvailable = false;
                $messages[] = 'The requested quantity exceeds current stock.';
            }
        }

        $subtotal = round($baseUnitPrice * $quantity, 2);
        $lineTotal = round($currentUnitPrice * $quantity, 2);
        $discountTotal = round(max($subtotal - $lineTotal, 0), 2);

        return [
            'id' => $cartItem->id,
            'product_id' => $cartItem->product_id,
            'vendor_id' => $cartItem->vendor_id,
            'quantity' => $quantity,
            'stored_unit_price' => $this->formatAmount((float) $cartItem->unit_price),
            'current_unit_price' => $this->formatAmount($currentUnitPrice),
            'base_unit_price' => $this->formatAmount($baseUnitPrice),
            'subtotal' => $this->formatAmount($subtotal),
            'discount_total' => $this->formatAmount($discountTotal),
            'line_total' => $this->formatAmount($lineTotal),
            'is_available' => $isAvailable,
            'messages' => $messages,
            'product' => $product,
            'vendor' => $cartItem->vendor,
        ];
    }

    private function formatAmount(float|int|string $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }
}
