<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check()
            && in_array(auth()->user()->role, ['admin', 'staff']);
    }

    public function rules(): array
    {
        return [
            'age_label' => ['sometimes', 'required', 'string', 'max:255'],
            'quantity_on_hand' => ['sometimes', 'required', 'integer', 'min:0'],
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

            $age = $this->has('age_label') ? $this->input('age_label') : $variant->age_label;

            $exists = ProductVariant::query()
                ->where('product_id', $productId)
                ->where('age_label', $age)
                ->where('id', '!=', $variantId)
                ->exists();

            if ($exists) {
                $validator->errors()->add(
                    'age_label',
                    'This age variant already exists for this product.'
                );
            }
        });
    }
}
