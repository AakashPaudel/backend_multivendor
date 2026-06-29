<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Vendor\PublicVendorResource;
use App\Services\Catalog\PublicVendorCatalogService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class VendorController extends Controller
{
    public function __construct(
        private readonly PublicVendorCatalogService $publicVendorCatalogService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return PublicVendorResource::collection(
            $this->publicVendorCatalogService->paginate((int) $request->integer('per_page', 15))
        );
    }

    public function show(string $slug): PublicVendorResource
    {
        return new PublicVendorResource(
            $this->publicVendorCatalogService->findBySlug($slug)
        );
    }
}
