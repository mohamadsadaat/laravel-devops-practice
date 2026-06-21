<?php

namespace App\Http\Requests\Storefront;

use Illuminate\Foundation\Http\FormRequest;

class StoreCheckoutRequest extends FormRequest
{
   public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:50'],
            'customer_address' => ['required', 'string', 'max:2000'],
            'city' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'shipping_fee' => ['nullable', 'numeric', 'min:0'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.age_label' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $seen = [];

            foreach ($this->input('items', []) as $index => $item) {
                if (!isset($item['product_id'], $item['age_label'])) {
                    continue;
                }

                $key = $item['product_id'] . '|' . mb_strtolower(trim((string) $item['age_label']));

                if (isset($seen[$key])) {
                    $validator->errors()->add(
                        "items.{$index}.age_label",
                        'Duplicate age selection for the same product.'
                    );
                }

                $seen[$key] = true;
            }
        });
    }
}
