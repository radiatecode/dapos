<?php

namespace DA\Inventory\Supports\Reports;

use DA\Inventory\DTO\Report\PurchaseReportExportDTO;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PurchaseReportExcelExport implements FromArray, ShouldAutoSize, WithColumnFormatting, WithStyles, WithTitle
{
    public function __construct(private PurchaseReportExportDTO $report) {}

    /**
     * @return list<list<string|int|float|null>>
     */
    public function array(): array
    {
        $totals = $this->report->totals;
        $sheet = [
            [$this->report->businessName],
            ['Purchase Report'],
        ];

        foreach ($this->report->filterLabels as $label => $value) {
            $sheet[] = [$label, $value];
        }

        $sheet[] = ['Generated at', $this->report->generatedAt];
        $sheet[] = [null];
        $sheet[] = ['Summary'];
        $sheet[] = ['Total purchases', $totals['total_purchases']];
        $sheet[] = ['Fully paid', $totals['paid_count']];
        $sheet[] = ['Total purchase amount', (float) $totals['total_amount']];
        $sheet[] = ['Total paid', (float) $totals['total_paid']];
        $sheet[] = ['Collected', $this->report->collectedPercent()];
        $sheet[] = ['Total due', (float) $totals['total_due']];
        $sheet[] = ['Purchases outstanding', $this->report->outstandingPurchases()];
        $sheet[] = [null];
        $sheet[] = $this->headings();

        $amount = 0.0;
        $paid = 0.0;
        $due = 0.0;

        foreach ($this->report->rows->values() as $index => $purchase) {
            $lineAmount = (float) $purchase->grand_total;
            $linePaid = (float) $purchase->paid_amount;
            $lineDue = (float) $purchase->due_amount;
            $amount += $lineAmount;
            $paid += $linePaid;
            $due += $lineDue;

            $sheet[] = [
                $index + 1,
                $purchase->po_number,
                $purchase->order_date?->format('d M Y'),
                $purchase->supplier?->name,
                $purchase->store?->name,
                $lineAmount,
                $linePaid,
                $lineDue,
                $purchase->status?->value,
                $purchase->payment_status?->value,
            ];
        }

        $sheet[] = [
            'Total', null, null, null, null,
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
            'F' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'G' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'H' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
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
        return 'Purchase Report';
    }

    /**
     * @return list<string>
     */
    private function headings(): array
    {
        $currencyCode = $this->report->numbers->currencyCode;

        return [
            '#',
            'PO',
            'Date',
            'Supplier',
            'Store',
            'Total ('.$currencyCode.')',
            'Paid ('.$currencyCode.')',
            'Due ('.$currencyCode.')',
            'Status',
            'Payment',
        ];
    }

    private function headingRowNumber(): int
    {
        return 2 + count($this->report->filterLabels) + 1 + 1 + 1 + 7 + 1 + 1;
    }
}
