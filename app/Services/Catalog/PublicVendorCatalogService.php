<?php

namespace App\Services\Catalog;

use App\Models\VendorProfile;
use App\Services\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class PublicVendorCatalogService extends Service
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return VendorProfile::query()
            ->withCount([
                'products' => fn (Builder $query) => $query->visible(),
            ])
            ->publiclyVisible()
            ->orderBy('store_name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findBySlug(string $slug): VendorProfile
    {
        return VendorProfile::query()
            ->withCount([
                'products' => fn (Builder $query) => $query->visible(),
            ])
            ->publiclyVisible()
            ->where('slug', $slug)
            ->firstOrFail();
    }
}
