<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'part_number' => ['required', 'string', 'max:255', 'unique:parts,part_number'],
            'part_name' => ['required', 'string', 'max:255'],
            'customer_code' => ['nullable', 'string', 'max:255'],
            'supplier_code' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'variant' => ['nullable', 'string', 'max:255'],
            'standard_packing' => ['required', 'integer', 'min:1'],
            'stock' => ['required', 'integer', 'min:0'],
            'address' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'part_number.required' => 'The part number is required.',
            'part_number.unique' => 'This part number already exists.',
            'part_name.required' => 'The part name is required.',
            'standard_packing.required' => 'The standard packing is required.',
            'standard_packing.min' => 'The standard packing must be at least 1.',
            'stock.required' => 'The stock quantity is required.',
            'stock.min' => 'The stock quantity cannot be negative.',
        ];
    }
}
