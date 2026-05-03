<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check()
            && in_array(auth()->user()->role, ['admin', 'staff']);
    }

    public function rules(): array
    {
        return [
            'sku' => ['nullable', 'string', 'max:255', Rule::unique('product_variants', 'sku')],
            'color_name' => ['required', 'string', 'max:255'],
            'size_name' => ['required', 'string', 'max:255'],
            'age_label' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'compare_price' => ['nullable', 'numeric', 'min:0', 'gte:price'],
            'quantity_on_hand' => ['nullable', 'integer', 'min:0'],
            'quantity_reserved' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            /** @var Product|string|null $product */
            $product = $this->route('product');
            $productId = $product instanceof Product ? $product->id : (int) $product;

            if (!$productId) {
                return;
            }

            $exists = ProductVariant::query()
                ->where('product_id', $productId)
                ->where('color_name', $this->input('color_name'))
                ->where('size_name', $this->input('size_name'))
                ->where('age_label', $this->input('age_label'))
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
