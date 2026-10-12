@php
    /** @var \DA\Inventory\DTO\Report\SalesReportExportDTO $report */
    $numbers = $report->numbers;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Report</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: "DejaVu Sans", Arial, sans-serif; font-size: 11px; color: #212529; margin: 0; }
        h1 { font-size: 18px; margin: 0 0 2px; }
        h2 { font-size: 14px; margin: 0 0 10px; color: #495057; font-weight: normal; }
        .meta { width: 100%; margin-bottom: 12px; border-collapse: collapse; }
        .meta td { padding: 2px 8px 2px 0; vertical-align: top; }
        .meta .label { color: #6c757d; width: 90px; }
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
    <h2>Sales Report</h2>

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

    <table class="report">
        <thead>
            <tr>
                <th style="width: 28px;">#</th>
                <th>SKU</th>
                <th>Product</th>
                <th>Brand</th>
                <th>Category</th>
                <th class="text-end">Sold Qty</th>
                <th class="text-end">Sold Amount ({{ $numbers->currencyCode }})</th>
                <th class="text-end">Paid Amount ({{ $numbers->currencyCode }})</th>
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
                    <td class="text-end">{{ $numbers->amount($row->sold_amount) }}</td>
                    <td class="text-end">{{ $numbers->amount($row->paid_amount) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="empty">No sales found for the selected filters.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5">Total ({{ $report->totals['total_products'] }} products)</td>
                <td class="text-end">{{ $numbers->quantity($report->totals['sold_quantity']) }}</td>
                <td class="text-end">{{ $numbers->amount($report->totals['sold_amount']) }}</td>
                <td class="text-end">{{ $numbers->amount($report->totals['paid_amount']) }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
