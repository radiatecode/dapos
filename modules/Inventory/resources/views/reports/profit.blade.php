@php
    /** @var \DA\Inventory\DTO\Report\ProfitReportExportDTO $report */
    $numbers = $report->numbers;
    $totals = $report->totals;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profit Report</title>
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
        table.summary .value.loss { color: #dc3545; }
        table.summary .note { color: #6c757d; font-size: 10px; }
        table.report { width: 100%; border-collapse: collapse; }
        table.report thead { display: table-header-group; }
        table.report tr { page-break-inside: avoid; }
        table.report th, table.report td { border: 1px solid #dee2e6; padding: 5px 6px; }
        table.report th { background: #e9ecef; text-align: left; }
        table.report tfoot td { font-weight: bold; background: #f8f9fa; }
        .text-end { text-align: right; }
        .muted { color: #6c757d; }
        .empty { text-align: center; padding: 16px; color: #6c757d; }
    </style>
</head>
<body>
    <h1>{{ $report->businessName }}</h1>
    <h2>Profit Report</h2>

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
                <div class="label">Net sales</div>
                <div class="value">{{ $numbers->amount($totals['net_sales']) }}</div>
                <div class="note">{{ $numbers->quantity($totals['sold_quantity']) }} units &middot; {{ $totals['total_products'] }} products</div>
            </td>
            <td>
                <div class="label">Cost of goods</div>
                <div class="value">{{ $numbers->amount($totals['cogs']) }}</div>
                <div class="note">Cost recorded on each sale</div>
            </td>
            <td>
                <div class="label">Gross profit</div>
                <div class="value {{ (float) $totals['gross_profit'] < 0 ? 'loss' : '' }}">{{ $numbers->amount($totals['gross_profit']) }}</div>
                <div class="note">{{ $report->lossNote() }}</div>
            </td>
            <td>
                <div class="label">Gross margin</div>
                <div class="value {{ (float) $totals['gross_margin'] < 0 ? 'loss' : '' }}">{{ $numbers->amount($totals['gross_margin']) }}%</div>
                <div class="note">Gross profit as a share of net sales</div>
            </td>
        </tr>
    </table>

    <table class="report">
        <thead>
            <tr>
                <th style="width: 28px;">#</th>
                <th>SKU</th>
                <th>Product</th>
                <th>Brand</th>
                <th>Category</th>
                <th class="text-end">Sold Qty</th>
                <th class="text-end">Net Sales ({{ $numbers->currencyCode }})</th>
                <th class="text-end">COGS ({{ $numbers->currencyCode }})</th>
                <th class="text-end">Gross Profit ({{ $numbers->currencyCode }})</th>
                <th class="text-end">Margin %</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($report->rows as $row)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $row->sku }}</td>
                    <td>
                        {{ $row->product_name }}
                        @if (filled($row->variant_name))
                            <span class="muted">({{ $row->variant_name }})</span>
                        @endif
                    </td>
                    <td>{{ $row->brand_name ?? '-' }}</td>
                    <td>{{ $row->category_name ?? '-' }}</td>
                    <td class="text-end">{{ $numbers->quantity($row->sold_quantity) }}</td>
                    <td class="text-end">{{ $numbers->amount($row->net_sales) }}</td>
                    <td class="text-end">{{ $numbers->amount($row->cogs) }}</td>
                    <td class="text-end">{{ $numbers->amount($row->gross_profit) }}</td>
                    <td class="text-end">{{ $numbers->amount($row->gross_margin) }}%</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="empty">No sales found for the selected filters.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5">Total ({{ $report->totals['total_products'] }} products)</td>
                <td class="text-end">{{ $numbers->quantity($report->totals['sold_quantity']) }}</td>
                <td class="text-end">{{ $numbers->amount($report->totals['net_sales']) }}</td>
                <td class="text-end">{{ $numbers->amount($report->totals['cogs']) }}</td>
                <td class="text-end">{{ $numbers->amount($report->totals['gross_profit']) }}</td>
                <td class="text-end">{{ $numbers->amount($report->totals['gross_margin']) }}%</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
