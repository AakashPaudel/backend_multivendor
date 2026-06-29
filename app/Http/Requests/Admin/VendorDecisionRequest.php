<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\ApiRequest;

class VendorDecisionRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string'],
        ];
    }
}
