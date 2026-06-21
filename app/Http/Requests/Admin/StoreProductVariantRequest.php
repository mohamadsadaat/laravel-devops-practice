<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Http\FormRequest;

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
            'age_label' => ['required', 'string', 'max:255'],
            'quantity_on_hand' => ['required', 'integer', 'min:0'],
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
                ->where('age_label', $this->input('age_label'))
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
