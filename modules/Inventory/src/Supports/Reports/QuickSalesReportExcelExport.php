<?php

namespace DA\Inventory\Supports\Reports;

use DA\Inventory\DTO\Report\QuickSalesReportExportDTO;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class QuickSalesReportExcelExport implements FromArray, ShouldAutoSize, WithColumnFormatting, WithStyles, WithTitle
{
    private const SUMMARY_ROWS = 8;

    public function __construct(private QuickSalesReportExportDTO $report) {}

    /**
     * @return list<list<string|int|float|null>>
     */
    public function array(): array
    {
        $totals = $this->report->totals;
        $sheet = [
            [$this->report->businessName],
            ['Quick Sales Report'],
        ];

        foreach ($this->report->filterLabels as $label => $value) {
            $sheet[] = [$label, $value];
        }

        $sheet[] = ['Generated at', $this->report->generatedAt];
        $sheet[] = [null];
        $sheet[] = ['Summary'];
        $sheet[] = ['Quick sales', $totals['total_quick_sales']];
        $sheet[] = ['Lines', $totals['total_items']];
        $sheet[] = ['Total amount', (float) $totals['total_amount']];
        $sheet[] = ['Average sale', (float) $totals['average_amount']];
        $sheet[] = ['Cash', (float) $totals['cash_amount']];
        $sheet[] = ['Card', (float) $totals['card_amount']];
        $sheet[] = ['Bank transfer', (float) $totals['bank_transfer_amount']];
        $sheet[] = ['Mobile banking', (float) $totals['mobile_banking_amount']];
        $sheet[] = [null];
        $sheet[] = $this->headings();

        foreach ($this->report->rows->values() as $index => $row) {
            $sheet[] = [
                $index + 1,
                Carbon::parse($row->sale_date)->format('d M Y'),
                (int) $row->quick_sales_count,
                (int) $row->total_items,
                (float) $row->cash_amount,
                (float) $row->card_amount,
                (float) $row->bank_transfer_amount,
                (float) $row->mobile_banking_amount,
                (float) $row->total_amount,
            ];
        }

        $sheet[] = [
            'Total', null,
            $totals['total_quick_sales'],
            $totals['total_items'],
            (float) $totals['cash_amount'],
            (float) $totals['card_amount'],
            (float) $totals['bank_transfer_amount'],
            (float) $totals['mobile_banking_amount'],
            (float) $totals['total_amount'],
        ];

        return $sheet;
    }

    /**
     * @return array<string, string>
     */
    public function columnFormats(): array
    {
        return [
            'E' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'F' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'G' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'H' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'I' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
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
        return 'Quick Sales Report';
    }

    /**
     * @return list<string>
     */
    private function headings(): array
    {
        $currencyCode = $this->report->numbers->currencyCode;

        return [
            '#',
            'Date',
            'Quick sales',
            'Lines',
            'Cash ('.$currencyCode.')',
            'Card ('.$currencyCode.')',
            'Bank transfer ('.$currencyCode.')',
            'Mobile banking ('.$currencyCode.')',
            'Total ('.$currencyCode.')',
        ];
    }

    private function headingRowNumber(): int
    {
        return 2 + count($this->report->filterLabels) + 1 + 1 + 1 + self::SUMMARY_ROWS + 1 + 1;
    }
}
