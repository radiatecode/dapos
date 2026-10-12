<?php

namespace DA\Inventory\Supports\Reports;

use DA\Inventory\DTO\Report\SalesInvoiceReportExportDTO;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalesInvoiceReportExcelExport implements FromArray, ShouldAutoSize, WithColumnFormatting, WithStyles, WithTitle
{
    public function __construct(private SalesInvoiceReportExportDTO $report) {}

    /**
     * @return list<list<string|int|float|null>>
     */
    public function array(): array
    {
        $totals = $this->report->totals;
        $sheet = [
            [$this->report->businessName],
            ['Sales Invoice Report'],
        ];

        foreach ($this->report->filterLabels as $label => $value) {
            $sheet[] = [$label, $value];
        }

        $sheet[] = ['Generated at', $this->report->generatedAt];
        $sheet[] = [null];
        $sheet[] = ['Summary'];
        $sheet[] = ['Total sales', $totals['total_sales']];
        $sheet[] = ['Fully paid', $totals['paid_count']];
        $sheet[] = ['Total sale amount', (float) $totals['total_amount']];
        $sheet[] = ['Total paid', (float) $totals['total_paid']];
        $sheet[] = ['Collected', $this->report->collectedPercent()];
        $sheet[] = ['Total due', (float) $totals['total_due']];
        $sheet[] = ['Invoices outstanding', $this->report->outstandingInvoices()];
        $sheet[] = [null];
        $sheet[] = $this->headings();

        $quantity = 0.0;
        $amount = 0.0;
        $paid = 0.0;
        $due = 0.0;

        foreach ($this->report->rows->values() as $index => $sale) {
            $lineQuantity = (float) $sale->total_items;
            $lineAmount = (float) $sale->grand_total;
            $linePaid = (float) $sale->paid_amount;
            $lineDue = (float) $sale->due_amount;
            $quantity += $lineQuantity;
            $amount += $lineAmount;
            $paid += $linePaid;
            $due += $lineDue;

            $sheet[] = [
                $index + 1,
                $sale->invoice_number,
                $sale->sale_date?->format('d M Y'),
                $sale->customer?->name ?? 'Walk-in customer',
                $sale->store?->name,
                $lineQuantity,
                $lineAmount,
                $linePaid,
                $lineDue,
                $sale->payment_status?->value,
            ];
        }

        $sheet[] = [
            'Total', null, null, null, null,
            $quantity,
            $amount,
            $paid,
            $due,
        ];

        return $sheet;
    }

    /**
     * @return array<string, string>
     */
    public function columnFormats(): array
    {
        return [
            'F' => NumberFormat::FORMAT_GENERAL,
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
        return 'Sales Invoice Report';
    }

    /**
     * @return list<string>
     */
    private function headings(): array
    {
        $currencyCode = $this->report->numbers->currencyCode;

        return [
            '#',
            'Invoice',
            'Date',
            'Customer',
            'Store',
            'Qty',
            'Total ('.$currencyCode.')',
            'Paid ('.$currencyCode.')',
            'Due ('.$currencyCode.')',
            'Payment',
        ];
    }

    private function headingRowNumber(): int
    {
        return 2 + count($this->report->filterLabels) + 1 + 1 + 1 + 7 + 1 + 1;
    }
}
