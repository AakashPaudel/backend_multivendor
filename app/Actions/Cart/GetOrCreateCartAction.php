<?php

namespace App\Actions\Cart;

use App\Models\Cart;
use App\Models\User;

class GetOrCreateCartAction
{
    public function handle(User $user): Cart
    {
        return Cart::query()->firstOrCreate([
            'user_id' => $user->id,
        ]);
    }
}
