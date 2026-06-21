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
                    'base_price' => $this->product?->base_price,
                ];
            }),

            'product_id' => $this->product_id,
            'age_label' => $this->age_label,
            'quantity_on_hand' => (int) $this->quantity_on_hand,
            'available_quantity' => (int) $this->available_quantity,
            'is_active' => (bool) $this->is_active,

            'stock_movements_count' => $this->whenCounted('stockMovements'),

            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
