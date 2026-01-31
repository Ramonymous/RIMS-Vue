<?php

namespace App\Imports;

use App\Models\Parts;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class PartsImport implements SkipsOnFailure, ToModel, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    public function model(array $row): Parts
    {
        return Parts::query()->updateOrCreate(
            ['part_number' => $row['part_number']],
            [
                'part_name' => $row['part_name'],
                'customer_code' => $row['customer_code'] ?? null,
                'supplier_code' => $row['supplier_code'] ?? null,
                'model' => $row['model'] ?? null,
                'variant' => $row['variant'] ?? null,
                'standard_packing' => (int) ($row['standard_packing'] ?? 1),
                'stock' => (int) ($row['stock'] ?? 0),
                'address' => $row['address'] ?? null,
                'is_active' => isset($row['is_active_1active_0inactive'])
                    ? (bool) $row['is_active_1active_0inactive']
                    : true,
            ]
        );
    }

    public function rules(): array
    {
        return [
            'part_number' => ['required', 'string', 'max:255'],
            'part_name' => ['required', 'string', 'max:255'],
            'customer_code' => ['nullable', 'string', 'max:255'],
            'supplier_code' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'variant' => ['nullable', 'string', 'max:255'],
            'standard_packing' => ['required', 'integer', 'min:1'],
            'stock' => ['required', 'integer', 'min:0'],
            'address' => ['nullable', 'string', 'max:255'],
            'is_active_1active_0inactive' => ['nullable', 'boolean'],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'part_number.required' => 'Part Number is required.',
            'part_number.unique' => 'Part Number already exists.',
            'part_name.required' => 'Part Name is required.',
            'standard_packing.required' => 'Standard Packing is required.',
            'standard_packing.min' => 'Standard Packing must be at least 1.',
            'stock.required' => 'Stock is required.',
            'stock.min' => 'Stock cannot be negative.',
        ];
    }
}
