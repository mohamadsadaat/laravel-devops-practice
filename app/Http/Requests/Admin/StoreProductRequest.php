<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check()
            && in_array(auth()->user()->role, ['admin', 'staff']);
    }

    protected function prepareForValidation(): void
    {
        if (!$this->filled('slug') && $this->filled('name')) {
            $this->merge([
                'slug' => Str::slug($this->name),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where('is_active', true)],
            'name' => ['required', 'string', 'max:255'],
            // 'slug' => ['required', 'string', 'max:255', Rule::unique('products', 'slug')],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['draft', 'active', 'archived'])],
            'base_price' => ['nullable', 'numeric', 'min:0'],
            'brand' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', Rule::in(['boy', 'girl', 'unisex'])],
            'is_featured' => ['nullable', 'boolean'],
        ];
    }
}
