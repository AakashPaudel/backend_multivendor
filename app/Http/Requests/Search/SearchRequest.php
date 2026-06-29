<?php

namespace App\Http\Requests\Search;

use App\Http\Requests\Catalog\ListProductsRequest;

class SearchRequest extends ListProductsRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'q' => ['nullable', 'string', 'max:255'],
        ];
    }
}
