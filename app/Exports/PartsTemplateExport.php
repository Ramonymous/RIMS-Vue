<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PartsTemplateExport implements FromArray, WithHeadings, WithStyles
{
    public function array(): array
    {
        // Return empty array for template (just headers)
        return [];
    }

    public function headings(): array
    {
        return [
            'Part Number *',
            'Part Name *',
            'Customer Code',
            'Supplier Code',
            'Model',
            'Variant',
            'Standard Packing *',
            'Stock *',
            'Address',
            'Is Active (1=Active, 0=Inactive)',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2E8F0'],
                ],
            ],
        ];
    }
}
