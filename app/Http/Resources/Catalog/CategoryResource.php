<?php

namespace App\Http\Resources\Catalog;

use App\Http\Resources\ApiResource;
use App\Services\Support\PublicMediaStorageService;
use Illuminate\Http\Request;

class CategoryResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        /** @var PublicMediaStorageService $media */
        $media = app(PublicMediaStorageService::class);

        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'image_path' => $this->image_path,
            'image_url' => $media->url($this->image_path),
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'children' => $this->whenLoaded('children', fn () => self::collection($this->children)),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
