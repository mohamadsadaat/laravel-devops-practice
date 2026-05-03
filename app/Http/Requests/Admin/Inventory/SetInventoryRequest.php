<?php

namespace App\Http\Requests\Admin\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class SetInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quantity_on_hand' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'quantity_on_hand.required' => 'Quantity on hand is required',
            'quantity_on_hand.integer' => 'Quantity on hand must be an integer',
            'quantity_on_hand.min' => 'Quantity on hand cannot be negative',
            'notes.string' => 'Notes must be a string',
            'notes.max' => 'Notes cannot exceed 500 characters',
        ];
    }
}
