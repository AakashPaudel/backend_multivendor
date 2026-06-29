<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\StoreProductRequest;
use App\Http\Requests\Vendor\UpdateProductRequest;
use App\Http\Resources\Product\ProductResource;
use App\Models\Product;
use App\Services\Vendor\VendorProductService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class VendorProductController extends Controller
{
    public function __construct(
        private readonly VendorProductService $vendorProductService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Product::class);

        return ProductResource::collection($this->vendorProductService->paginateForVendor($request->user()));
    }

    public function store(StoreProductRequest $request): ProductResource
    {
        $this->authorize('create', Product::class);

        return new ProductResource(
            $this->vendorProductService->create($request->user(), $request->validated())
        );
    }

    public function show(Product $product): ProductResource
    {
        $this->authorize('view', $product);

        return new ProductResource($product->load(['category', 'images', 'vendor.vendorProfile']));
    }

    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        $this->authorize('update', $product);

        return new ProductResource(
            $this->vendorProductService->update($request->user(), $product, $request->validated())
        );
    }

    public function destroy(Product $product): Response
    {
        $this->authorize('delete', $product);

        $this->vendorProductService->delete($product);

        return response()->noContent();
    }
}
