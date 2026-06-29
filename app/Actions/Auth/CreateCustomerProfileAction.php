<?php

namespace App\Actions\Auth;

use App\Actions\Action;
use App\Models\CustomerProfile;
use App\Models\User;

class CreateCustomerProfileAction extends Action
{
    public function handle(User $user): CustomerProfile
    {
        return CustomerProfile::query()->create([
            'user_id' => $user->id,
        ]);
    }
}
