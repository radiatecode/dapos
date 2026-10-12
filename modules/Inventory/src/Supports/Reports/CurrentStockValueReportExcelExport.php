<?php

namespace DA\Inventory\Supports\Reports;

use DA\Inventory\DTO\Report\CurrentStockValueReportExportDTO;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CurrentStockValueReportExcelExport implements FromArray, ShouldAutoSize, WithColumnFormatting, WithStyles, WithTitle
{
    public function __construct(private CurrentStockValueReportExportDTO $report) {}

    /**
     * @return list<list<string|int|float|null>>
     */
    public function array(): array
    {
        $totals = $this->report->totals;
        $currencyCode = $this->report->numbers->currencyCode;
        $sheet = [
            [$this->report->businessName],
            ['Current Stock Value'],
        ];

        foreach ($this->report->filterLabels as $label => $value) {
            $sheet[] = [$label, $value];
        }

        $sheet[] = ['Generated at', $this->report->generatedAt];
        $sheet[] = [null];
        $sheet[] = ['Summary'];
        $sheet[] = ['Available products', $totals['available_products']];
        $sheet[] = ['Available quantity', (float) $totals['total_available_quantity']];
        $sheet[] = ['On-hand quantity', (float) $totals['total_on_hand_quantity']];
        $sheet[] = ['Stock value ('.$currencyCode.')', (float) $totals['total_stock_value']];
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
                (float) $row->available_quantity,
                (float) $row->on_hand_quantity,
                (float) $row->unit_cost,
                (float) $row->stock_value,
            ];
        }

        $sheet[] = [
            'Total', null, null, null, null, null, null,
            (float) $totals['total_available_quantity'],
            (float) $totals['total_on_hand_quantity'],
            null,
            (float) $totals['total_stock_value'],
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
            'J' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'K' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
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
        return 'Current Stock Value';
    }

    /**
     * @return list<string>
     */
    private function headings(): array
    {
        $currencyCode = $this->report->numbers->currencyCode;

        return [
            '#',
            'SKU',
            'Product',
            'Variant',
            'Brand',
            'Category',
            'Unit',
            'Available',
            'On hand',
            'Avg cost ('.$currencyCode.')',
            'Stock value ('.$currencyCode.')',
        ];
    }

    private function headingRowNumber(): int
    {
        return 2 + count($this->report->filterLabels) + 1 + 1 + 1 + 4 + 1 + 1;
    }
}
