@php
    /** @var \DA\Inventory\DTO\Report\MinStockReportExportDTO $report */
    $numbers = $report->numbers;
    $totals = $report->totals;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Min Stock Level Report</title>
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
        table.summary .value.alert { color: #dc3545; }
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
        .status-out_of_stock { color: #dc3545; font-weight: bold; }
        .status-low_stock { color: #b8860b; font-weight: bold; }
    </style>
</head>
<body>
    <h1>{{ $report->businessName }}</h1>
    <h2>Min Stock Level Report</h2>

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
                <div class="label">Items to reorder</div>
                <div class="value">{{ $totals['total_items'] }}</div>
                <div class="note">At or below their min stock level</div>
            </td>
            <td>
                <div class="label">Out of stock</div>
                <div class="value {{ $totals['out_of_stock_items'] > 0 ? 'alert' : '' }}">{{ $totals['out_of_stock_items'] }}</div>
                <div class="note">No stock left to sell</div>
            </td>
            <td>
                <div class="label">Low stock</div>
                <div class="value">{{ $totals['low_stock_items'] }}</div>
                <div class="note">Still in stock, but running low</div>
            </td>
            <td>
                <div class="label">Total reorder qty</div>
                <div class="value">{{ $numbers->quantity($totals['shortage_quantity']) }}</div>
                <div class="note">Needed to bring {{ $totals['total_items'] === 1 ? '1 item' : $totals['total_items'].' items' }} back to the min level</div>
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
                <th class="text-end">Current Stock</th>
                <th class="text-end">Min Stock Level</th>
                <th class="text-end">Reorder Qty</th>
                <th>Status</th>
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
                    <td class="text-end">{{ $numbers->quantity($row->current_stock) }} {{ $row->unit_code }}</td>
                    <td class="text-end">{{ $numbers->quantity($row->min_stock_level) }}</td>
                    <td class="text-end">{{ $numbers->quantity($row->shortage_quantity) }}</td>
                    <td class="status-{{ $row->stock_status }}">{{ \DA\Inventory\Enums\MinStockStatus::from($row->stock_status)->label() }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="empty">No items are at or below their min stock level.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="7">Listed items ({{ $report->rows->count() }})</td>
                <td class="text-end">{{ $numbers->quantity($totals['shortage_quantity']) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
