<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\ListProductsRequest;
use App\Http\Resources\Product\ProductResource;
use App\Services\Catalog\PublicProductCatalogService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function __construct(
        private readonly PublicProductCatalogService $publicProductCatalogService,
    ) {}

    public function index(ListProductsRequest $request): AnonymousResourceCollection
    {
        return ProductResource::collection(
            $this->publicProductCatalogService->paginate($request->validated())
        );
    }

    public function show(string $slug): ProductResource
    {
        return new ProductResource(
            $this->publicProductCatalogService->findVisibleBySlug($slug)
        );
    }
}
