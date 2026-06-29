<?php

namespace App\Http\Requests\Catalog;

use App\Http\Requests\ApiRequest;

class ListProductsRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'category' => ['nullable', 'string', 'max:255'],
            'vendor' => ['nullable', 'string', 'max:255'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'sort' => ['nullable', 'in:newest,price_asc,price_desc,popularity'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }
}
