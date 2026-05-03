<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
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

            'variant' => $this->whenLoaded('variant', function () {
                return $this->variant
                    ? [
                        'id' => $this->variant->id,
                        'sku' => $this->variant->sku,
                        'color_name' => $this->variant->color_name,
                        'size_name' => $this->variant->size_name,
                        'age_label' => $this->variant->age_label,
                    ]
                    : null;
            }),

            'product_id' => $this->product_id,
            'variant_id' => $this->variant_id,
            'product_name_snapshot' => $this->product_name_snapshot,
            'variant_snapshot' => $this->variant_snapshot,
            'sku_snapshot' => $this->sku_snapshot,
            'unit_price' => $this->unit_price,
            'quantity' => (int) $this->quantity,
            'line_total' => $this->line_total,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}