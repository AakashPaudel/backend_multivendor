<?php

namespace App\Http\Requests\Customer;

use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerProfileRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:30'],
            'default_address_id' => [
                'nullable',
                'integer',
                Rule::exists('addresses', 'id')->where(
                    fn ($query) => $query->where('user_id', $this->user()->id)
                ),
            ],
        ];
    }
}
