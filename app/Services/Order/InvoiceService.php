<?php

namespace App\Services\Order;

use App\Models\Order;
use App\Services\Service;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceService extends Service
{
    public function generate(Order $order): string
    {
        $order->loadMissing([
            'user',
            'address',
            'vendorOrders.vendor.vendorProfile',
            'items.vendor.vendorProfile',
            'payments' => fn ($query) => $query->latest('id'),
        ]);

        $path = $this->pathForOrder($order);

        $this->disk()->put($path, $this->renderHtml($order));

        return $path;
    }

    public function response(Order $order): StreamedResponse
    {
        $path = $this->generate($order);

        return $this->disk()->response(
            $path,
            'invoice-'.$order->order_number.'.html',
            ['Content-Type' => 'text/html; charset=UTF-8']
        );
    }

    private function disk(): Filesystem
    {
        return Storage::disk('local');
    }

    private function pathForOrder(Order $order): string
    {
        return 'invoices/orders/'.$order->order_number.'.html';
    }

    private function renderHtml(Order $order): string
    {
        $latestPayment = $order->payments->first();
        $address = $order->address;
        $customerName = e($order->user->name);
        $customerEmail = e($order->user->email);
        $gatewayReference = e((string) ($latestPayment?->gateway_reference ?? 'N/A'));
        $recipientName = e((string) ($address?->recipient_name ?? $order->user->name));
        $addressLineOne = e((string) ($address?->address_line_1 ?? ''));
        $addressLineTwo = e((string) ($address?->address_line_2 ?? ''));
        $city = e((string) ($address?->city ?? ''));
        $district = e((string) ($address?->district ?? ''));
        $country = e((string) ($address?->country ?? ''));

        $rows = $order->items
            ->map(function ($item): string {
                return sprintf(
                    '<tr><td>%s</td><td>%s</td><td style="text-align:right">%s</td><td style="text-align:right">%d</td><td style="text-align:right">%s</td></tr>',
                    e($item->product_name_snapshot),
                    e($item->sku_snapshot),
                    number_format((float) $item->unit_price, 2, '.', ''),
                    $item->quantity,
                    number_format((float) $item->line_total, 2, '.', '')
                );
            })
            ->implode('');

        $vendorBlocks = $order->vendorOrders
            ->map(function ($vendorOrder): string {
                return sprintf(
                    '<li>%s - subtotal %s, commission %s, net %s</li>',
                    e($vendorOrder->vendor->vendorProfile?->store_name ?? $vendorOrder->vendor->name),
                    number_format((float) $vendorOrder->subtotal, 2, '.', ''),
                    number_format((float) $vendorOrder->commission_amount, 2, '.', ''),
                    number_format((float) $vendorOrder->net_amount, 2, '.', '')
                );
            })
            ->implode('');

        return <<<HTML
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Invoice {$order->order_number}</title>
  <style>
    body { font-family: Arial, sans-serif; color: #111827; margin: 32px; }
    h1, h2, h3 { margin-bottom: 8px; }
    table { width: 100%; border-collapse: collapse; margin-top: 16px; }
    th, td { border: 1px solid #d1d5db; padding: 8px; }
    th { background: #f3f4f6; text-align: left; }
    .meta { margin-bottom: 24px; }
    .totals { margin-top: 20px; width: 320px; margin-left: auto; }
    .totals td { border: none; padding: 4px 0; }
  </style>
</head>
<body>
  <h1>Invoice</h1>
  <div class="meta">
    <p><strong>Order Number:</strong> {$order->order_number}</p>
    <p><strong>Customer:</strong> {$customerName} ({$customerEmail})</p>
    <p><strong>Placed At:</strong> {$order->placed_at?->toDateTimeString()}</p>
    <p><strong>Order Status:</strong> {$order->order_status->value}</p>
    <p><strong>Payment Status:</strong> {$order->payment_status->value}</p>
    <p><strong>Gateway Reference:</strong> {$gatewayReference}</p>
  </div>

  <h2>Shipping Address</h2>
  <p>
    {$recipientName}<br>
    {$addressLineOne}<br>
    {$addressLineTwo}<br>
    {$city}, {$district}<br>
    {$country}
  </p>

  <h2>Items</h2>
  <table>
    <thead>
      <tr>
        <th>Product</th>
        <th>SKU</th>
        <th>Unit Price</th>
        <th>Qty</th>
        <th>Line Total</th>
      </tr>
    </thead>
    <tbody>
      {$rows}
    </tbody>
  </table>

  <h2>Vendor Breakdown</h2>
  <ul>{$vendorBlocks}</ul>

  <table class="totals">
    <tr><td><strong>Subtotal</strong></td><td style="text-align:right">{$order->subtotal}</td></tr>
    <tr><td><strong>Discount</strong></td><td style="text-align:right">{$order->discount_total}</td></tr>
    <tr><td><strong>Shipping</strong></td><td style="text-align:right">{$order->shipping_total}</td></tr>
    <tr><td><strong>Tax</strong></td><td style="text-align:right">{$order->tax_total}</td></tr>
    <tr><td><strong>Grand Total</strong></td><td style="text-align:right">{$order->grand_total}</td></tr>
  </table>
</body>
</html>
HTML;
    }
}
