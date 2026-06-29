<?php

namespace App\Http\Requests\Admin;

use App\Enums\ProductStatus;
use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateProductStatusRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'status' => ['required', new Enum(ProductStatus::class)],
        ];
    }
}
