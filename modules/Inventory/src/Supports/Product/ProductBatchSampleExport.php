<?php

namespace DA\Inventory\Supports\Product;

use DA\Inventory\DTO\Product\ProductBatchSampleDTO;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductBatchSampleExport implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    public function __construct(private ProductBatchSampleDTO $sample) {}

    /**
     * @return list<list<string>>
     */
    public function array(): array
    {
        return [
            [$this->sample->companyName],
            [$this->sample->address],
            ProductBatchColumns::HEADINGS,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            ProductBatchColumns::HEADER_ROW => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1B4F72'],
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'Products';
    }
}
