<?php

namespace App\Services\Auth;

use App\Enums\UserStatus;
use App\Models\User;
use App\Services\Service;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginService extends Service
{
    public function handle(array $credentials): array
    {
        $user = User::query()
            ->with(['customerProfile.defaultAddress', 'vendorProfile'])
            ->where('email', $credentials['email'])
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->status !== UserStatus::Active) {
            throw ValidationException::withMessages([
                'email' => ['This account is not active.'],
            ]);
        }

        return [
            'message' => 'Login successful.',
            'token' => $user->createToken($credentials['device_name'] ?? 'api-token')->plainTextToken,
            'user' => $user,
        ];
    }
}
