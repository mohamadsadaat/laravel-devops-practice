<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category?->id,
                    'name' => $this->category?->name,
                    'slug' => $this->category?->slug,
                ];
            }),

            'category_id' => $this->category_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'status' => $this->status,
            'base_price' => $this->base_price,
            'brand' => $this->brand,
            'gender' => $this->gender,
            'is_featured' => (bool) $this->is_featured,

            'variants_count' => $this->whenCounted('variants'),
            'images_count' => $this->whenCounted('images'),

            'primary_image_url' => $this->when(
                $this->relationLoaded('images') && $this->images->isNotEmpty(),
                function () {
                    $primaryImage = $this->images->where('is_primary', true)->first()
                        ?? $this->images->sortBy('sort_order')->first();
                    
                    if ($primaryImage && $primaryImage->path) {
                        return Storage::disk('public')->url($primaryImage->path);
                    }
                    return null;
                }
            ),

            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
