<?php

namespace App\Http\Resources\Auth;

use App\Http\Resources\ApiResource;
use Illuminate\Http\Request;

class AuthenticatedUserResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'message' => $this['message'],
            'token' => $this['token'],
            'token_type' => 'Bearer',
            'user' => new UserResource($this['user']),
        ];
    }
}
