@php
    /** @var \DA\Inventory\DTO\Report\QuickSalesReportExportDTO $report */
    $numbers = $report->numbers;
    $totals = $report->totals;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quick Sales Report</title>
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
    <h2>Quick Sales Report</h2>

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
                <div class="label">Quick sales</div>
                <div class="value">{{ $totals['total_quick_sales'] }}</div>
                <div class="note">{{ $totals['total_items'] }} lines</div>
            </td>
            <td>
                <div class="label">Total amount</div>
                <div class="value">{{ $numbers->amount($totals['total_amount']) }}</div>
                <div class="note">Average {{ $numbers->amount($totals['average_amount']) }} per sale</div>
            </td>
            <td>
                <div class="label">Cash</div>
                <div class="value">{{ $numbers->amount($totals['cash_amount']) }}</div>
                <div class="note">Collected in cash</div>
            </td>
            <td>
                <div class="label">Card &amp; digital</div>
                <div class="value">{{ $numbers->amount($report->nonCashAmount()) }}</div>
                <div class="note">Card, bank transfer and mobile banking</div>
            </td>
        </tr>
    </table>

    <table class="report">
        <thead>
            <tr>
                <th style="width: 28px;">#</th>
                <th>Date</th>
                <th class="text-end">Quick sales</th>
                <th class="text-end">Lines</th>
                <th class="text-end">Cash ({{ $numbers->currencyCode }})</th>
                <th class="text-end">Card ({{ $numbers->currencyCode }})</th>
                <th class="text-end">Bank transfer ({{ $numbers->currencyCode }})</th>
                <th class="text-end">Mobile banking ({{ $numbers->currencyCode }})</th>
                <th class="text-end">Total ({{ $numbers->currencyCode }})</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($report->rows as $row)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ \Illuminate\Support\Carbon::parse($row->sale_date)->format('d M Y') }}</td>
                    <td class="text-end">{{ $row->quick_sales_count }}</td>
                    <td class="text-end">{{ $row->total_items }}</td>
                    <td class="text-end">{{ $numbers->amount($row->cash_amount) }}</td>
                    <td class="text-end">{{ $numbers->amount($row->card_amount) }}</td>
                    <td class="text-end">{{ $numbers->amount($row->bank_transfer_amount) }}</td>
                    <td class="text-end">{{ $numbers->amount($row->mobile_banking_amount) }}</td>
                    <td class="text-end">{{ $numbers->amount($row->total_amount) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="empty">No quick sales found for the selected filters.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">Total ({{ $report->rows->count() }} days)</td>
                <td class="text-end">{{ $totals['total_quick_sales'] }}</td>
                <td class="text-end">{{ $totals['total_items'] }}</td>
                <td class="text-end">{{ $numbers->amount($totals['cash_amount']) }}</td>
                <td class="text-end">{{ $numbers->amount($totals['card_amount']) }}</td>
                <td class="text-end">{{ $numbers->amount($totals['bank_transfer_amount']) }}</td>
                <td class="text-end">{{ $numbers->amount($totals['mobile_banking_amount']) }}</td>
                <td class="text-end">{{ $numbers->amount($totals['total_amount']) }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
