<?php

namespace DA\Inventory\Supports\Reports;

use DA\Inventory\DTO\Report\SalesReportExportDTO;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalesReportExcelExport implements FromArray, ShouldAutoSize, WithColumnFormatting, WithStyles, WithTitle
{
    public function __construct(private SalesReportExportDTO $report) {}

    /**
     * @return list<list<string|int|float|null>>
     */
    public function array(): array
    {
        $sheet = [
            [$this->report->businessName],
            ['Sales Report'],
        ];

        foreach ($this->report->filterLabels as $label => $value) {
            $sheet[] = [$label, $value];
        }

        $sheet[] = ['Generated at', $this->report->generatedAt];
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
                (float) $row->sold_quantity,
                (float) $row->sold_amount,
                (float) $row->paid_amount,
            ];
        }

        $totals = $this->report->totals;
        $sheet[] = [
            'Total', null, null, null, null, null,
            (float) $totals['sold_quantity'],
            (float) $totals['sold_amount'],
            (float) $totals['paid_amount'],
        ];

        return $sheet;
    }

    /**
     * @return array<string, string>
     */
    public function columnFormats(): array
    {
        return [
            'G' => NumberFormat::FORMAT_GENERAL,
            'H' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'I' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }

    /**
     * @return array<int|string, array<string, mixed>>
     */
    public function styles(Worksheet $sheet): array
    {
        $headingRow = $this->headingRowNumber();
        $totalRow = $headingRow + $this->report->rows->count() + 1;

        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            2 => ['font' => ['bold' => true, 'size' => 12]],
            $headingRow => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'E9ECEF']],
            ],
            $totalRow => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return 'Sales Report';
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
            'Sold Qty',
            'Sold Amount ('.$currencyCode.')',
            'Paid Amount ('.$currencyCode.')',
        ];
    }

    private function headingRowNumber(): int
    {
        return 2 + count($this->report->filterLabels) + 1 + 1 + 1;
    }
}
