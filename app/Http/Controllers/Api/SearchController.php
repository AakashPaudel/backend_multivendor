<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Search\SearchRequest;
use App\Http\Resources\Product\ProductResource;
use App\Services\Catalog\PublicProductCatalogService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SearchController extends Controller
{
    public function __construct(
        private readonly PublicProductCatalogService $publicProductCatalogService,
    ) {}

    public function __invoke(SearchRequest $request): AnonymousResourceCollection
    {
        return ProductResource::collection(
            $this->publicProductCatalogService->search($request->validated())
        );
    }
}
