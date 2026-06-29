<?php

namespace App\Http\Requests\Admin;

use App\Enums\OrderStatus;
use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateOrderStatusRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'status' => ['required', new Enum(OrderStatus::class)],
            'message' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
