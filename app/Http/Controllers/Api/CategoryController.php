<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Catalog\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return CategoryResource::collection(
            Category::query()
                ->active()
                ->with(['children' => fn ($query) => $query->active()->orderBy('sort_order')->orderBy('id')])
                ->whereNull('parent_id')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->paginate()
        );
    }

    public function show(Category $category): CategoryResource
    {
        abort_unless($category->is_active, 404);

        return new CategoryResource($category->load(['children' => fn ($query) => $query->active()->orderBy('sort_order')->orderBy('id')]));
    }
}
