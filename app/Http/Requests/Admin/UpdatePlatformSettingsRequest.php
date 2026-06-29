<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\ApiRequest;

class UpdatePlatformSettingsRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'settings' => ['required', 'array', 'min:1'],
            'settings.*.key' => ['required', 'string', 'max:255'],
            'settings.*.value' => ['nullable', 'array'],
        ];
    }
}
