<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check()
            && in_array(auth()->user()->role, ['admin', 'staff']);
    }

    public function rules(): array
    {
        /** @var ProductVariant|string|null $variant */
        $variant = $this->route('variant');

        return [
            'sku' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('product_variants', 'sku')->ignore($variant),
            ],
            'color_name' => ['sometimes', 'required', 'string', 'max:255'],
            'size_name' => ['sometimes', 'required', 'string', 'max:255'],
            'age_label' => ['sometimes', 'required', 'string', 'max:255'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'compare_price' => ['nullable', 'numeric', 'min:0'],
            'quantity_on_hand' => ['sometimes', 'required', 'integer', 'min:0'],
            'quantity_reserved' => ['sometimes', 'required', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            /** @var Product|string|null $product */
            $product = $this->route('product');

            /** @var ProductVariant|string|null $variant */
            $variant = $this->route('variant');

            $productId = $product instanceof Product ? $product->id : (int) $product;
            $variantId = $variant instanceof ProductVariant ? $variant->id : (int) $variant;

            if (!$productId || !$variantId) {
                return;
            }

            $color = $this->has('color_name') ? $this->input('color_name') : $variant->color_name;
            $size = $this->has('size_name') ? $this->input('size_name') : $variant->size_name;
            $age = $this->has('age_label') ? $this->input('age_label') : $variant->age_label;

            $exists = ProductVariant::query()
                ->where('product_id', $productId)
                ->where('color_name', $color)
                ->where('size_name', $size)
                ->where('age_label', $age)
                ->where('id', '!=', $variantId)
                ->exists();

            if ($exists) {
                $validator->errors()->add(
                    'color_name',
                    'This variant combination already exists for this product.'
                );
            }
        });
    }
}
