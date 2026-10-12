<?php

namespace DA\Inventory\Supports\Reports;

use DA\Inventory\DTO\Report\MinStockReportExportDTO;
use DA\Inventory\Enums\MinStockStatus;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MinStockReportExcelExport implements FromArray, ShouldAutoSize, WithColumnFormatting, WithStyles, WithTitle
{
    public function __construct(private MinStockReportExportDTO $report) {}

    /**
     * @return list<list<string|int|float|null>>
     */
    public function array(): array
    {
        $totals = $this->report->totals;
        $sheet = [
            [$this->report->businessName],
            ['Min Stock Level Report'],
        ];

        foreach ($this->report->filterLabels as $label => $value) {
            $sheet[] = [$label, $value];
        }

        $sheet[] = ['Generated at', $this->report->generatedAt];
        $sheet[] = [null];
        $sheet[] = ['Summary'];
        $sheet[] = ['Items to reorder', $totals['total_items']];
        $sheet[] = ['Out of stock', $totals['out_of_stock_items']];
        $sheet[] = ['Low stock', $totals['low_stock_items']];
        $sheet[] = ['Total reorder qty', (float) $totals['shortage_quantity']];
        $sheet[] = [null];
        $sheet[] = $this->headings();

        foreach ($this->report->rows->values() as $index => $row) {
            $sheet[] = [
                $index + 1,
                $row->sku,
                $row->product_name,
                $row->variant_name,
                $row->brand_name,
                $row->category_name,
                $row->unit_code,
                (float) $row->current_stock,
                (int) $row->min_stock_level,
                (float) $row->shortage_quantity,
                MinStockStatus::from($row->stock_status)->label(),
            ];
        }

        $sheet[] = [
            'Total', null, null, null, null, null, null, null, null,
            (float) $totals['shortage_quantity'],
        ];

        return $sheet;
    }

    /**
     * @return array<string, string>
     */
    public function columnFormats(): array
    {
        return [
            'H' => NumberFormat::FORMAT_GENERAL,
            'I' => NumberFormat::FORMAT_GENERAL,
            'J' => NumberFormat::FORMAT_GENERAL,
        ];
    }

    /**
     * @return array<int|string, array<string, mixed>>
     */
    public function styles(Worksheet $sheet): array
    {
        $summaryRow = 2 + count($this->report->filterLabels) + 1 + 1 + 1;
        $headingRow = $this->headingRowNumber();
        $totalRow = $headingRow + $this->report->rows->count() + 1;

        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            2 => ['font' => ['bold' => true, 'size' => 12]],
            $summaryRow => ['font' => ['bold' => true, 'size' => 12]],
            $headingRow => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'E9ECEF']],
            ],
            $totalRow => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return 'Min Stock Level Report';
    }

    /**
     * @return list<string>
     */
    private function headings(): array
    {
        return [
            '#',
            'SKU',
            'Product',
            'Variant',
            'Brand',
            'Category',
            'Unit',
            'Current Stock',
            'Min Stock Level',
            'Reorder Qty',
            'Status',
        ];
    }

    private function headingRowNumber(): int
    {
        return 2 + count($this->report->filterLabels) + 1 + 1 + 1 + 4 + 1 + 1;
    }
}
