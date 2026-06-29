<?php

namespace App\Services\Admin;

use App\Models\Category;
use App\Services\Service;
use App\Services\Support\PublicMediaStorageService;
use App\Services\Support\SlugService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CategoryService extends Service
{
    public function __construct(
        private readonly SlugService $slugService,
        private readonly PublicMediaStorageService $mediaStorageService,
    ) {}

    public function create(array $attributes): Category
    {
        return DB::transaction(function () use ($attributes): Category {
            $category = new Category;

            $category->fill([
                'parent_id' => $attributes['parent_id'] ?? null,
                'name' => $attributes['name'],
                'slug' => $attributes['slug'] ?? $this->slugService->generate($attributes['name'], Category::class),
                'description' => $attributes['description'] ?? null,
                'is_active' => $attributes['is_active'] ?? true,
                'sort_order' => $attributes['sort_order'] ?? 0,
            ]);

            if (isset($attributes['image'])) {
                $category->image_path = $this->mediaStorageService->store($attributes['image'], 'categories');
            }

            $category->save();

            Cache::forget('catalog:categories:index');

            return $category->fresh();
        });
    }

    public function update(Category $category, array $attributes): Category
    {
        return DB::transaction(function () use ($attributes, $category): Category {
            $name = $attributes['name'] ?? $category->name;

            $category->fill([
                'parent_id' => array_key_exists('parent_id', $attributes) ? $attributes['parent_id'] : $category->parent_id,
                'name' => $name,
                'slug' => $attributes['slug'] ?? $this->slugService->generate($name, Category::class, ignoreId: $category->id),
                'description' => $attributes['description'] ?? $category->description,
                'is_active' => $attributes['is_active'] ?? $category->is_active,
                'sort_order' => $attributes['sort_order'] ?? $category->sort_order,
            ]);

            if (isset($attributes['image'])) {
                $category->image_path = $this->mediaStorageService->replace($category->image_path, $attributes['image'], 'categories');
            }

            $category->save();

            Cache::forget('catalog:categories:index');

            return $category->fresh();
        });
    }

    public function delete(Category $category): void
    {
        DB::transaction(function () use ($category): void {
            $this->mediaStorageService->delete($category->image_path);
            $category->delete();
            Cache::forget('catalog:categories:index');
        });
    }
}
