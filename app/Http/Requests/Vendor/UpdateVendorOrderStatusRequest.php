<?php

namespace App\Http\Requests\Vendor;

use App\Enums\VendorOrderStatus;
use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateVendorOrderStatusRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'status' => ['required', new Enum(VendorOrderStatus::class)],
            'message' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
