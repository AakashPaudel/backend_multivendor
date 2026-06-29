<?php

namespace App\Services\Checkout;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentVerificationStatus;
use App\Enums\VendorOrderStatus;
use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Models\VendorOrder;
use App\Services\Order\CommissionService;
use App\Services\Payment\EsewaPaymentService;
use App\Services\Service;
use App\Services\Support\MarketplaceNotificationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutPreparationService extends Service
{
    public function __construct(
        private readonly CommissionService $commissionService,
        private readonly EsewaPaymentService $esewaPaymentService,
        private readonly MarketplaceNotificationService $marketplaceNotificationService,
    ) {}

    public function prepare(User $user, array $attributes): array
    {
        return DB::transaction(function () use ($user, $attributes): array {
            $address = Address::query()
                ->whereBelongsTo($user)
                ->find($attributes['address_id']);

            if (! $address) {
                throw ValidationException::withMessages([
                    'address_id' => ['The selected shipping address is invalid.'],
                ]);
            }

            $cart = Cart::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (! $cart) {
                throw ValidationException::withMessages([
                    'cart' => ['Your cart is empty.'],
                ]);
            }

            $cartItems = CartItem::query()
                ->whereBelongsTo($cart)
                ->lockForUpdate()
                ->get();

            if ($cartItems->isEmpty()) {
                throw ValidationException::withMessages([
                    'cart' => ['Your cart is empty.'],
                ]);
            }

            $products = Product::query()
                ->with(['vendor.vendorProfile'])
                ->whereIn('id', $cartItems->pluck('product_id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $checkoutLines = $cartItems
                ->map(fn (CartItem $cartItem): array => $this->buildCheckoutLine($cartItem, $products))
                ->values();

            $subtotal = $checkoutLines->sum('subtotal');
            $discountTotal = $checkoutLines->sum('discount_total');
            $shippingTotal = 0.00;
            $taxTotal = 0.00;
            $grandTotal = $checkoutLines->sum('line_total') + $shippingTotal + $taxTotal;

            $order = Order::query()->create([
                'order_number' => $this->generateOrderNumber(),
                'user_id' => $user->id,
                'address_id' => $address->id,
                'subtotal' => $this->formatAmount($subtotal),
                'discount_total' => $this->formatAmount($discountTotal),
                'shipping_total' => $this->formatAmount($shippingTotal),
                'tax_total' => $this->formatAmount($taxTotal),
                'grand_total' => $this->formatAmount($grandTotal),
                'payment_status' => PaymentStatus::Initiated,
                'order_status' => OrderStatus::PaymentInitiated,
                'notes' => $attributes['notes'] ?? null,
                'placed_at' => now(),
            ]);

            $vendorOrders = $this->createVendorOrders($order, $checkoutLines);

            $payment = Payment::query()->create([
                'order_id' => $order->id,
                'payment_method' => 'esewa',
                'gateway' => 'esewa',
                'amount' => $this->formatAmount($grandTotal),
                'transaction_uuid' => (string) Str::uuid(),
                'status' => PaymentStatus::Initiated,
                'verification_status' => PaymentVerificationStatus::Pending,
            ]);

            $paymentInitiation = $this->esewaPaymentService->buildInitiationPayload($order, $payment);

            $payment->update([
                'raw_request_json' => [
                    ...$paymentInitiation,
                    'transaction_uuid' => $payment->transaction_uuid,
                    'amount' => $paymentInitiation['fields']['amount'],
                    'tax_amount' => $paymentInitiation['fields']['tax_amount'],
                    'total_amount' => $paymentInitiation['fields']['total_amount'],
                ],
            ]);

            $order->statusHistories()->create([
                'status' => OrderStatus::PaymentInitiated->value,
                'message' => 'Checkout prepared and payment initiated.',
                'changed_by' => $user->id,
                'created_at' => now(),
            ]);

            $cart->items()->delete();

            $order->refresh();
            $payment->refresh();
            $this->marketplaceNotificationService->sendOrderPlaced($order->loadMissing('vendorOrders.vendor', 'user'));

            return [
                'order' => $order,
                'totals' => [
                    'subtotal' => $this->formatAmount($subtotal),
                    'discount_total' => $this->formatAmount($discountTotal),
                    'shipping_total' => $this->formatAmount($shippingTotal),
                    'tax_total' => $this->formatAmount($taxTotal),
                    'grand_total' => $this->formatAmount($grandTotal),
                ],
                'vendor_orders' => $vendorOrders,
                'payment' => $payment,
                'payment_initiation' => [
                    ...$paymentInitiation,
                    'amount' => $paymentInitiation['fields']['amount'],
                    'tax_amount' => $paymentInitiation['fields']['tax_amount'],
                    'total_amount' => $paymentInitiation['fields']['total_amount'],
                    'order_number' => $order->order_number,
                ],
            ];
        });
    }

    private function buildCheckoutLine(CartItem $cartItem, Collection $products): array
    {
        $product = $products->get($cartItem->product_id);

        if (! $product || ! $product->isSellable()) {
            throw ValidationException::withMessages([
                'cart' => ['One or more cart items are no longer available for purchase.'],
            ]);
        }

        if ($cartItem->quantity > $product->stock_quantity) {
            throw ValidationException::withMessages([
                'cart' => ['One or more cart items exceed the available stock.'],
            ]);
        }

        $baseUnitPrice = (float) $product->price;
        $currentUnitPrice = $product->currentUnitPrice();
        $quantity = $cartItem->quantity;
        $subtotal = round($baseUnitPrice * $quantity, 2);
        $lineTotal = round($currentUnitPrice * $quantity, 2);
        $discountTotal = round(max($subtotal - $lineTotal, 0), 2);
        $commissionRate = $this->commissionService->resolveRateForVendor($product->vendor);
        $commissionBreakdown = $this->commissionService->calculateBreakdown($lineTotal, $commissionRate);

        return [
            'cart_item_id' => $cartItem->id,
            'product' => $product,
            'vendor' => $product->vendor,
            'quantity' => $quantity,
            'unit_price' => $currentUnitPrice,
            'subtotal' => $subtotal,
            'discount_total' => $discountTotal,
            'line_total' => $lineTotal,
            'commission_rate' => $commissionBreakdown['commission_rate'],
            'commission_amount' => $commissionBreakdown['commission_amount'],
            'net_amount' => $commissionBreakdown['net_amount'],
        ];
    }

    private function createVendorOrders(Order $order, Collection $checkoutLines): array
    {
        return $checkoutLines
            ->groupBy(fn (array $line): int => $line['vendor']->id)
            ->map(function (Collection $vendorLines) use ($order): array {
                $firstLine = $vendorLines->first();
                $vendor = $firstLine['vendor'];
                $vendorOrder = VendorOrder::query()->create([
                    'order_id' => $order->id,
                    'vendor_id' => $vendor->id,
                    'subtotal' => $this->formatAmount($vendorLines->sum('line_total')),
                    'commission_amount' => $this->formatAmount($vendorLines->sum('commission_amount')),
                    'net_amount' => $this->formatAmount($vendorLines->sum('net_amount')),
                    'status' => VendorOrderStatus::New,
                ]);

                $vendorLines->each(function (array $line) use ($order, $vendorOrder, $vendor): void {
                    $product = $line['product'];

                    OrderItem::query()->create([
                        'order_id' => $order->id,
                        'vendor_order_id' => $vendorOrder->id,
                        'product_id' => $product->id,
                        'vendor_id' => $vendor->id,
                        'product_name_snapshot' => $product->name,
                        'sku_snapshot' => $product->sku,
                        'unit_price' => $this->formatAmount($line['unit_price']),
                        'quantity' => $line['quantity'],
                        'line_total' => $this->formatAmount($line['line_total']),
                        'commission_amount' => $this->formatAmount($line['commission_amount']),
                        'net_amount' => $this->formatAmount($line['net_amount']),
                        'status' => VendorOrderStatus::New->value,
                    ]);
                });

                $order->statusHistories()->create([
                    'vendor_order_id' => $vendorOrder->id,
                    'status' => VendorOrderStatus::New->value,
                    'message' => sprintf('Vendor order created for vendor %d.', $vendor->id),
                    'changed_by' => $order->user_id,
                    'created_at' => now(),
                ]);

                return [
                    'id' => $vendorOrder->id,
                    'vendor_id' => $vendor->id,
                    'store_name' => $vendor->vendorProfile?->store_name,
                    'subtotal' => $vendorOrder->subtotal,
                    'commission_rate' => $this->formatAmount($firstLine['commission_rate']),
                    'commission_amount' => $vendorOrder->commission_amount,
                    'net_amount' => $vendorOrder->net_amount,
                    'items_count' => $vendorLines->count(),
                ];
            })
            ->values()
            ->all();
    }

    private function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'ORD-'.now()->format('YmdHis').'-'.Str::upper(Str::random(6));
        } while (Order::query()->where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    private function formatAmount(float|int|string $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }
}
