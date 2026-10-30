<?php

namespace DA\Expense\Supports\Reports;

use DA\Expense\DTO\Report\ExpenseReportExportDTO;
use DA\Expense\Models\Expense;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExpenseReportExcelExport implements FromArray, ShouldAutoSize, WithColumnFormatting, WithStyles, WithTitle
{
    public function __construct(private ExpenseReportExportDTO $report) {}

    /**
     * @return list<list<string|int|float|null>>
     */
    public function array(): array
    {
        $totals = $this->report->totals;
        $sheet = [
            [$this->report->businessName],
            ['Expense Report'],
        ];

        foreach ($this->report->filterLabels as $label => $value) {
            $sheet[] = [$label, $value];
        }

        $sheet[] = ['Generated at', $this->report->generatedAt];
        $sheet[] = [null];
        $sheet[] = ['Summary'];
        $sheet[] = ['Expenses', $totals['total_expenses']];
        $sheet[] = ['Total amount', (float) $totals['total_amount']];
        $sheet[] = ['Approved amount', (float) $totals['approved_amount']];
        $sheet[] = ['Pending amount', (float) $totals['pending_amount']];
        $sheet[] = [null];
        $sheet[] = $this->headings();

        $amount = 0.0;

        foreach ($this->report->rows->values() as $index => $expense) {
            $lineAmount = (float) $expense->amount;
            $amount += $lineAmount;

            $sheet[] = [
                $index + 1,
                $this->expenseTitle($expense),
                $expense->category?->category_name,
                $expense->store?->name,
                $expense->expense_date?->format('d M Y'),
                $lineAmount,
                $expense->status?->label(),
            ];
        }

        $sheet[] = [
            'Total', null, null, null, null,
            $amount,
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
        return 'Expense Report';
    }

    /**
     * @return list<string>
     */
    private function headings(): array
    {
        $currencyCode = $this->report->numbers->currencyCode;

        return [
            '#',
            'Title',
            'Category',
            'Branch',
            'Date',
            'Amount ('.$currencyCode.')',
            'Status',
        ];
    }

    private function headingRowNumber(): int
    {
        return 2 + count($this->report->filterLabels) + 1 + 1 + 1 + 4 + 1 + 1;
    }

    private function expenseTitle(Expense $expense): string
    {
        $description = trim((string) $expense->description);

        if ($description !== '') {
            return $description;
        }

        return $expense->category?->category_name ?? '-';
    }
}
