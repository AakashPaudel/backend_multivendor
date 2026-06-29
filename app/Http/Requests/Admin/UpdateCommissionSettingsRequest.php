<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\ApiRequest;

class UpdateCommissionSettingsRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'global_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'vendor_overrides' => ['nullable', 'array'],
            'vendor_overrides.*.vendor_id' => ['required', 'integer', 'exists:users,id'],
            'vendor_overrides.*.rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
