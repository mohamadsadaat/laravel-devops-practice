<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CollectionWithProductsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'image_path' => $this->image_path,
            'image_url' => $this->image_path ? "/storage/{$this->image_path}" : null,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'products_count' => $this->when(
                $this->resource->relationLoaded('products'),
                $this->products->count()
            ),
            'products' => $this->when(
                $this->resource->relationLoaded('products'),
                $this->products->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'slug' => $product->slug,
                        'category' => [
                            'id' => $product->category->id,
                            'name' => $product->category->name,
                            'slug' => $product->category->slug,
                        ],
                        'brand' => $product->brand,
                        'gender' => $product->gender,
                        'base_price' => (float) $product->base_price,
                        'status' => $product->status,
                        'is_featured' => (bool) $product->is_featured,
                        'variants_count' => $product->variants_count,
                        'images_count' => $product->images_count,
                        'primary_image' => $product->images->where('is_primary', true)->first()?->path 
                            ?? $product->images->first()?->path,
                        'images' => $product->images->map(function ($image) {
                            return [
                                'id' => $image->id,
                                'path' => $image->path,
                                'url' => $image->path,
                                'alt_text' => $image->alt_text,
                                'is_primary' => (bool) $image->is_primary,
                                'sort_order' => $image->sort_order,
                            ];
                        }),
                    ];
                })
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
