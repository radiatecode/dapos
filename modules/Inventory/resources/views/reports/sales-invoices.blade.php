@php
    /** @var \DA\Inventory\DTO\Report\SalesInvoiceReportExportDTO $report */
    $numbers = $report->numbers;
    $totals = $report->totals;
    $outstanding = $report->outstandingInvoices();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Invoice Report</title>
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
    <h2>Sales Invoice Report</h2>

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
                <div class="label">Total sales</div>
                <div class="value">{{ $totals['total_sales'] }}</div>
                <div class="note">{{ $totals['paid_count'] }} fully paid</div>
            </td>
            <td>
                <div class="label">Total sale amount</div>
                <div class="value">{{ $numbers->amount($totals['total_amount']) }}</div>
                <div class="note">Grand total of matching sales</div>
            </td>
            <td>
                <div class="label">Total paid</div>
                <div class="value">{{ $numbers->amount($totals['total_paid']) }}</div>
                <div class="note">{{ $report->collectedPercent() }} collected</div>
            </td>
            <td>
                <div class="label">Total due</div>
                <div class="value">{{ $numbers->amount($totals['total_due']) }}</div>
                <div class="note">{{ $outstanding === 1 ? '1 invoice outstanding' : $outstanding.' invoices outstanding' }}</div>
            </td>
        </tr>
    </table>

    <table class="report">
        <thead>
            <tr>
                <th style="width: 28px;">#</th>
                <th>Invoice</th>
                <th>Date</th>
                <th>Customer</th>
                <th>Store</th>
                <th class="text-end">Qty</th>
                <th class="text-end">Total ({{ $numbers->currencyCode }})</th>
                <th class="text-end">Paid ({{ $numbers->currencyCode }})</th>
                <th class="text-end">Due ({{ $numbers->currencyCode }})</th>
                <th>Payment</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($report->rows as $sale)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $sale->invoice_number }}</td>
                    <td>{{ $sale->sale_date?->format('d M Y') }}</td>
                    <td>{{ $sale->customer?->name ?? 'Walk-in customer' }}</td>
                    <td>{{ $sale->store?->name ?? '-' }}</td>
                    <td class="text-end">{{ $numbers->quantity($sale->total_items) }}</td>
                    <td class="text-end">{{ $numbers->amount($sale->grand_total) }}</td>
                    <td class="text-end">{{ $numbers->amount($sale->paid_amount) }}</td>
                    <td class="text-end">{{ $numbers->amount($sale->due_amount) }}</td>
                    <td>{{ $sale->payment_status?->value }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="empty">No sales found for the selected filters.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5">Listed invoices ({{ $report->rows->count() }})</td>
                <td class="text-end">{{ $numbers->quantity($report->rows->sum('total_items')) }}</td>
                <td class="text-end">{{ $numbers->amount($report->rows->sum('grand_total')) }}</td>
                <td class="text-end">{{ $numbers->amount($report->rows->sum('paid_amount')) }}</td>
                <td class="text-end">{{ $numbers->amount($report->rows->sum('due_amount')) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
