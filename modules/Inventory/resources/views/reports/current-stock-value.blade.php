@php
    /** @var \DA\Inventory\DTO\Report\CurrentStockValueReportExportDTO $report */
    $numbers = $report->numbers;
    $totals = $report->totals;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Current Stock Value</title>
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
        .muted { color: #6c757d; }
        .empty { text-align: center; padding: 16px; color: #6c757d; }
    </style>
</head>
<body>
    <h1>{{ $report->businessName }}</h1>
    <h2>Current Stock Value</h2>

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
                <div class="label">Available products</div>
                <div class="value">{{ $totals['available_products'] }}</div>
                <div class="note">Variants that can be sold now</div>
            </td>
            <td>
                <div class="label">Available quantity</div>
                <div class="value">{{ $numbers->quantity($totals['total_available_quantity']) }}</div>
                <div class="note">Ready to sell</div>
            </td>
            <td>
                <div class="label">On-hand quantity</div>
                <div class="value">{{ $numbers->quantity($totals['total_on_hand_quantity']) }}</div>
                <div class="note">Includes reserved stock</div>
            </td>
            <td>
                <div class="label">Stock value ({{ $numbers->currencyCode }})</div>
                <div class="value">{{ $numbers->amount($totals['total_stock_value']) }}</div>
                <div class="note">On-hand quantity × average cost</div>
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
                <th class="text-end">Available</th>
                <th class="text-end">On hand</th>
                <th class="text-end">Avg cost ({{ $numbers->currencyCode }})</th>
                <th class="text-end">Stock value ({{ $numbers->currencyCode }})</th>
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
                    <td class="text-end">{{ $numbers->quantity($row->available_quantity) }} {{ $row->unit_code }}</td>
                    <td class="text-end">{{ $numbers->quantity($row->on_hand_quantity) }} {{ $row->unit_code }}</td>
                    <td class="text-end">{{ $numbers->amount($row->unit_cost) }}</td>
                    <td class="text-end">{{ $numbers->amount($row->stock_value) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="empty">No products have stock on hand.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5">Listed items ({{ $report->rows->count() }})</td>
                <td class="text-end">{{ $numbers->quantity($totals['total_available_quantity']) }}</td>
                <td class="text-end">{{ $numbers->quantity($totals['total_on_hand_quantity']) }}</td>
                <td></td>
                <td class="text-end">{{ $numbers->amount($totals['total_stock_value']) }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
