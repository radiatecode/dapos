<?php

namespace DA\Inventory\Supports\Reports;

use DA\Inventory\DTO\Report\ProfitReportExportDTO;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProfitReportExcelExport implements FromArray, ShouldAutoSize, WithColumnFormatting, WithStyles, WithTitle
{
    public function __construct(private ProfitReportExportDTO $report) {}

    /**
     * @return list<list<string|int|float|null>>
     */
    public function array(): array
    {
        $totals = $this->report->totals;
        $sheet = [
            [$this->report->businessName],
            ['Profit Report'],
        ];

        foreach ($this->report->filterLabels as $label => $value) {
            $sheet[] = [$label, $value];
        }

        $sheet[] = ['Generated at', $this->report->generatedAt];
        $sheet[] = [null];
        $sheet[] = ['Summary'];
        $sheet[] = ['Products sold', $totals['total_products']];
        $sheet[] = ['Quantity sold', (float) $totals['sold_quantity']];
        $sheet[] = ['Net sales', (float) $totals['net_sales']];
        $sheet[] = ['Cost of goods', (float) $totals['cogs']];
        $sheet[] = ['Gross profit', (float) $totals['gross_profit']];
        $sheet[] = ['Products sold at a loss', $totals['loss_products']];
        $sheet[] = ['Gross margin %', (float) $totals['gross_margin']];
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
                (float) $row->net_sales,
                (float) $row->cogs,
                (float) $row->gross_profit,
                (float) $row->gross_margin,
            ];
        }

        $sheet[] = [
            'Total', null, null, null, null, null,
            (float) $totals['sold_quantity'],
            (float) $totals['net_sales'],
            (float) $totals['cogs'],
            (float) $totals['gross_profit'],
            (float) $totals['gross_margin'],
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
        return 'Profit Report';
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
            'Net Sales ('.$currencyCode.')',
            'COGS ('.$currencyCode.')',
            'Gross Profit ('.$currencyCode.')',
            'Margin %',
        ];
    }

    private function headingRowNumber(): int
    {
        return 2 + count($this->report->filterLabels) + 1 + 1 + 1 + 7 + 1 + 1;
    }
}
