<?php

namespace App\Exports;

use App\Models\Parts;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockReportExport implements FromCollection, ShouldAutoSize, WithColumnWidths, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Parts::query()->orderBy('part_number');

        // Apply status filter
        if (isset($this->filters['status']) && $this->filters['status'] !== 'all') {
            $query->where('is_active', $this->filters['status'] === 'active');
        }

        // Apply search filter
        if (isset($this->filters['search']) && ! empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('part_number', 'like', "%{$search}%")
                    ->orWhere('part_name', 'like', "%{$search}%");
            });
        }

        // Apply stock level filter
        if (isset($this->filters['stock_level'])) {
            switch ($this->filters['stock_level']) {
                case 'out_of_stock':
                    $query->where('stock', 0);
                    break;
                case 'low':
                    $query->where('stock', '>', 0)->where('stock', '<', 10);
                    break;
                case 'available':
                    $query->where('stock', '>=', 10);
                    break;
            }
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Part Number',
            'Part Name',
            'Customer Code',
            'Supplier Code',
            'Model',
            'Variant',
            'Standard Packing',
            'Current Stock',
            'Stock Level',
            'Stock Value',
            'Address',
            'Status',
            'Last Updated',
        ];
    }

    public function map($part): array
    {
        $stockLevel = $this->getStockLevel($part->stock);

        return [
            $part->part_number,
            $part->part_name,
            $part->customer_code ?? '-',
            $part->supplier_code ?? '-',
            $part->model ?? '-',
            $part->variant ?? '-',
            $part->standard_packing,
            $part->stock,
            $stockLevel,
            $part->stock * $part->standard_packing, // Stock value in units
            $part->address ?? '-',
            $part->is_active ? 'Active' : 'Inactive',
            $part->updated_at->format('Y-m-d H:i:s'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();

        return [
            // Header row styling
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 11,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F46E5'], // Indigo color
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
            // All cells border
            "A1:M{$lastRow}" => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'E2E8F0'],
                    ],
                ],
            ],
            // Data rows alignment
            "H2:H{$lastRow}" => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
                'font' => [
                    'bold' => true,
                ],
            ],
            "I2:I{$lastRow}" => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ],
            "J2:J{$lastRow}" => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'Stock Report';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 18, // Part Number
            'B' => 30, // Part Name
            'C' => 15, // Customer Code
            'D' => 15, // Supplier Code
            'E' => 15, // Model
            'F' => 12, // Variant
            'G' => 15, // Standard Packing
            'H' => 12, // Current Stock
            'I' => 15, // Stock Level
            'J' => 12, // Stock Value
            'K' => 20, // Address
            'L' => 10, // Status
            'M' => 18, // Last Updated
        ];
    }

    protected function getStockLevel(int $stock): string
    {
        if ($stock === 0) {
            return 'Out of Stock';
        } elseif ($stock < 10) {
            return 'Low Stock';
        }

        return 'Available';
    }
}
