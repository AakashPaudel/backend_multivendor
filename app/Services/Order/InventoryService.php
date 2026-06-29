<?php

namespace App\Services\Order;

use App\Exceptions\InsufficientStockException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\Service;
use Illuminate\Support\Collection;

class InventoryService extends Service
{
    public function deductStockForOrder(Order $order): void
    {
        $productQuantities = $this->productQuantitiesForOrder($order);

        if ($productQuantities->isEmpty()) {
            return;
        }

        $products = Product::query()
            ->whereIn('id', $productQuantities->keys())
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($productQuantities as $productId => $quantity) {
            /** @var Product|null $product */
            $product = $products->get($productId);

            if (! $product) {
                throw new InsufficientStockException('One or more products for this order are no longer available.');
            }

            if ((int) $product->stock_quantity < $quantity) {
                throw new InsufficientStockException(sprintf(
                    'Insufficient stock for product "%s". Manual review is required.',
                    $product->name
                ));
            }
        }

        foreach ($productQuantities as $productId => $quantity) {
            $product = $products->get($productId);

            if (! $product) {
                continue;
            }

            $product->decrement('stock_quantity', $quantity);
        }
    }

    public function restoreStockForOrder(Order $order): void
    {
        $productQuantities = $this->productQuantitiesForOrder($order);

        if ($productQuantities->isEmpty()) {
            return;
        }

        $products = Product::query()
            ->whereIn('id', $productQuantities->keys())
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($productQuantities as $productId => $quantity) {
            $product = $products->get($productId);

            if (! $product) {
                continue;
            }

            $product->increment('stock_quantity', $quantity);
        }
    }

    private function productQuantitiesForOrder(Order $order): Collection
    {
        return OrderItem::query()
            ->where('order_id', $order->id)
            ->select(['product_id', 'quantity'])
            ->get()
            ->groupBy('product_id')
            ->map(fn (Collection $items): int => (int) $items->sum('quantity'));
    }
}
