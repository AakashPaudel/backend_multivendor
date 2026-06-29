<?php

namespace App\Services\Auth;

use App\Actions\Auth\CreateVendorProfileAction;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Services\Service;
use App\Services\Support\MarketplaceNotificationService;
use Illuminate\Support\Facades\DB;

class RegisterVendorService extends Service
{
    public function __construct(
        private readonly CreateVendorProfileAction $createVendorProfileAction,
        private readonly MarketplaceNotificationService $marketplaceNotificationService,
    ) {}

    public function handle(array $attributes): array
    {
        $user = DB::transaction(function () use ($attributes): User {
            $user = User::query()->create([
                'name' => $attributes['name'],
                'email' => $attributes['email'],
                'phone' => $attributes['phone'],
                'password' => $attributes['password'],
                'role' => UserRole::Vendor,
                'status' => UserStatus::Active,
            ]);

            $this->createVendorProfileAction->handle($user, $attributes);

            return $user;
        });

        $user->sendEmailVerificationNotification();
        $this->marketplaceNotificationService->sendAccountCreated($user);

        return [
            'message' => 'Vendor registration successful.',
            'token' => $user->createToken('vendor-registration')->plainTextToken,
            'user' => $user->loadMissing('vendorProfile'),
        ];
    }
}
