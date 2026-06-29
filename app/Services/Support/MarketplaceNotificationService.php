<?php

namespace App\Services\Support;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Models\VendorProfile;
use App\Notifications\MarketplaceLifecycleNotification;
use App\Services\Service;

class MarketplaceNotificationService extends Service
{
    public function sendAccountCreated(User $user): void
    {
        $message = $user->isVendor()
            ? 'Your vendor account has been created and is pending approval.'
            : 'Your customer account has been created successfully.';

        $user->notify(new MarketplaceLifecycleNotification(
            'account.created',
            'Account created',
            $message,
            [
                'user_id' => $user->id,
                'role' => $user->role->value,
            ]
        ));
    }

    public function sendVendorDecision(VendorProfile $profile): void
    {
        $profile->loadMissing('user');

        $title = match ($profile->approval_status->value) {
            'approved' => 'Vendor account approved',
            'rejected' => 'Vendor account rejected',
            'suspended' => 'Vendor account suspended',
            default => 'Vendor account updated',
        };

        $message = match ($profile->approval_status->value) {
            'approved' => 'Your store can now publish and sell products.',
            'rejected' => 'Your vendor account was rejected. Please review the provided reason.',
            'suspended' => 'Your vendor account has been suspended.',
            default => 'Your vendor account status was updated.',
        };

        $profile->user->notify(new MarketplaceLifecycleNotification(
            'vendor.approval_status_changed',
            $title,
            $message,
            [
                'vendor_profile_id' => $profile->id,
                'approval_status' => $profile->approval_status->value,
                'reason' => $profile->rejection_reason,
            ]
        ));
    }

    public function sendOrderPlaced(Order $order): void
    {
        $order->loadMissing(['user', 'vendorOrders.vendor']);

        $order->user->notify(new MarketplaceLifecycleNotification(
            'order.placed',
            'Order placed',
            'Your order has been created and is awaiting payment verification.',
            [
                'order_number' => $order->order_number,
                'order_id' => $order->id,
            ]
        ));

        $order->vendorOrders
            ->pluck('vendor')
            ->filter()
            ->unique('id')
            ->each(function (User $vendor) use ($order): void {
                $vendor->notify(new MarketplaceLifecycleNotification(
                    'vendor.order_received',
                    'New vendor order received',
                    'A new vendor order has been created for your store.',
                    [
                        'order_number' => $order->order_number,
                        'order_id' => $order->id,
                    ]
                ));
            });
    }

    public function sendPaymentStatus(Order $order, Payment $payment, string $type, string $message): void
    {
        $order->loadMissing(['user', 'vendorOrders.vendor']);

        $payload = [
            'order_number' => $order->order_number,
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'payment_status' => $payment->status->value,
            'verification_status' => $payment->verification_status->value,
        ];

        $order->user->notify(new MarketplaceLifecycleNotification(
            'payment.status_changed',
            'Payment update',
            $message,
            $payload
        ));

        if ($payment->status->value === 'paid') {
            $order->vendorOrders
                ->pluck('vendor')
                ->filter()
                ->unique('id')
                ->each(function (User $vendor) use ($payload): void {
                    $vendor->notify(new MarketplaceLifecycleNotification(
                        'vendor.payment_verified',
                        'Payment verified',
                        'A customer payment has been verified for one of your orders.',
                        $payload
                    ));
                });
        }
    }

    public function sendOrderStatusChanged(Order $order, string $message): void
    {
        $order->loadMissing(['user', 'vendorOrders.vendor']);

        $payload = [
            'order_number' => $order->order_number,
            'order_id' => $order->id,
            'order_status' => $order->order_status->value,
            'payment_status' => $order->payment_status->value,
        ];

        $order->user->notify(new MarketplaceLifecycleNotification(
            'order.status_changed',
            'Order status updated',
            $message,
            $payload
        ));

        $order->vendorOrders
            ->pluck('vendor')
            ->filter()
            ->unique('id')
            ->each(function (User $vendor) use ($message, $payload): void {
                $vendor->notify(new MarketplaceLifecycleNotification(
                    'vendor.order_status_changed',
                    'Order status updated',
                    $message,
                    $payload
                ));
            });
    }
}
