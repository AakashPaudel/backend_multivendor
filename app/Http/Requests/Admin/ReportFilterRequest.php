<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\ApiRequest;

class ReportFilterRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'role' => ['nullable', 'string', 'in:admin,vendor,customer'],
            'status' => ['nullable', 'string', 'in:active,inactive,suspended'],
        ];
    }
}
