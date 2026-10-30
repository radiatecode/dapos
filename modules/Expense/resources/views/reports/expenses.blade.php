@php
    /** @var \DA\Expense\DTO\Report\ExpenseReportExportDTO $report */
    $numbers = $report->numbers;
    $totals = $report->totals;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Expense Report</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: "DejaVu Sans", Arial, sans-serif; font-size: 11px; color: #212529; margin: 0; }
        h1 { font-size: 18px; margin: 0 0 2px; }
        h2 { font-size: 14px; margin: 0 0 10px; color: #495057; font-weight: normal; }
        .meta { width: 100%; margin-bottom: 12px; border-collapse: collapse; }
        .meta td { padding: 2px 8px 2px 0; vertical-align: top; }
        .meta .label { color: #6c757d; width: 90px; }
        table.summary { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        table.summary td { width: 25%; border: 1px solid #dee2e6; padding: 8px 10px; vertical-align: top; }
        table.summary .label { color: #6c757d; font-size: 10px; }
        table.summary .value { font-size: 16px; font-weight: bold; margin: 2px 0; }
        table.summary .note { color: #6c757d; font-size: 10px; }
        table.report { width: 100%; border-collapse: collapse; }
        table.report thead { display: table-header-group; }
        table.report tr { page-break-inside: avoid; }
        table.report th, table.report td { border: 1px solid #dee2e6; padding: 5px 6px; }
        table.report th { background: #e9ecef; text-align: left; }
        table.report tfoot td { font-weight: bold; background: #f8f9fa; }
        .text-end { text-align: right; }
        .empty { text-align: center; padding: 16px; color: #6c757d; }
    </style>
</head>
<body>
    <h1>{{ $report->businessName }}</h1>
    <h2>Expense Report</h2>

    <table class="meta">
        @foreach ($report->filterLabels as $label => $value)
            <tr>
                <td class="label">{{ $label }}</td>
                <td>{{ $value }}</td>
            </tr>
        @endforeach
        <tr>
            <td class="label">Generated at</td>
            <td>{{ $report->generatedAt }}</td>
        </tr>
    </table>

    <table class="summary">
        <tr>
            <td>
                <div class="label">Expenses</div>
                <div class="value">{{ $totals['total_expenses'] }}</div>
                <div class="note">{{ $totals['approved_count'] }} approved</div>
            </td>
            <td>
                <div class="label">Total amount</div>
                <div class="value">{{ $numbers->amount($totals['total_amount']) }}</div>
                <div class="note">Matching expenses</div>
            </td>
            <td>
                <div class="label">Approved amount</div>
                <div class="value">{{ $numbers->amount($totals['approved_amount']) }}</div>
                <div class="note">{{ $totals['approved_count'] }} approved</div>
            </td>
            <td>
                <div class="label">Pending amount</div>
                <div class="value">{{ $numbers->amount($totals['pending_amount']) }}</div>
                <div class="note">{{ $totals['pending_count'] }} awaiting approval</div>
            </td>
        </tr>
    </table>

    <table class="report">
        <thead>
            <tr>
                <th style="width: 28px;">#</th>
                <th>Title</th>
                <th>Category</th>
                <th>Branch</th>
                <th>Date</th>
                <th class="text-end">Amount ({{ $numbers->currencyCode }})</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($report->rows as $expense)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ trim((string) $expense->description) !== '' ? $expense->description : ($expense->category?->category_name ?? '-') }}</td>
                    <td>{{ $expense->category?->category_name ?? '-' }}</td>
                    <td>{{ $expense->store?->name ?? '-' }}</td>
                    <td>{{ $expense->expense_date?->format('d M Y') }}</td>
                    <td class="text-end">{{ $numbers->amount($expense->amount) }}</td>
                    <td>{{ $expense->status?->label() }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="empty">No expenses found for the selected filters.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5">Listed expenses ({{ $report->rows->count() }})</td>
                <td class="text-end">{{ $numbers->amount($report->rows->sum('amount')) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
