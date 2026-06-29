<?php

namespace App\Services\Auth;

use App\Actions\Auth\CreateCustomerProfileAction;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Services\Service;
use App\Services\Support\MarketplaceNotificationService;
use Illuminate\Support\Facades\DB;

class RegisterCustomerService extends Service
{
    public function __construct(
        private readonly CreateCustomerProfileAction $createCustomerProfileAction,
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
                'role' => UserRole::Customer,
                'status' => UserStatus::Active,
            ]);

            $this->createCustomerProfileAction->handle($user);

            return $user;
        });

        $user->sendEmailVerificationNotification();
        $this->marketplaceNotificationService->sendAccountCreated($user);

        return [
            'message' => 'Customer registration successful.',
            'token' => $user->createToken('customer-registration')->plainTextToken,
            'user' => $user->loadMissing('customerProfile.defaultAddress'),
        ];
    }
}
