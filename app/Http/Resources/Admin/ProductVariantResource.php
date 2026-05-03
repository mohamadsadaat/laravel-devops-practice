<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'product' => $this->whenLoaded('product', function () {
                return [
                    'id' => $this->product?->id,
                    'name' => $this->product?->name,
                    'slug' => $this->product?->slug,
                ];
            }),

            'product_id' => $this->product_id,
            'sku' => $this->sku,
            'color_name' => $this->color_name,
            'size_name' => $this->size_name,
            'age_label' => $this->age_label,
            'price' => $this->price,
            'compare_price' => $this->compare_price,
            'quantity_on_hand' => (int) $this->quantity_on_hand,
            'quantity_reserved' => (int) $this->quantity_reserved,
            'available_quantity' => (int) $this->available_quantity,
            'is_active' => (bool) $this->is_active,

            'images_count' => $this->whenCounted('images'),
            'stock_movements_count' => $this->whenCounted('stockMovements'),

            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}