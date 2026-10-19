@php
    /** @var \DA\Inventory\DTO\PurchaseReturn\PurchaseReturnDocument $document */
    $return = $document->purchaseReturn;
    $numbers = $document->numbers;
    $supplier = $return->supplier;
    $store = $return->store;
    $supplierAddress = collect([
        $supplier?->address,
        collect([$supplier?->city, $supplier?->state])
            ->filter()
            ->join(', '),
        $supplier?->postal_code,
        $supplier?->country,
    ])
        ->filter(fn($line) => is_string($line) && trim($line) !== '')
        ->all();
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Purchase Return {{ $return->return_number }}</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: "DejaVu Sans", Arial, sans-serif;
            font-size: 11px;
            color: #212529;
            margin: 0;
        }

        h1 {
            font-size: 18px;
            margin: 0 0 2px;
        }

        h2 {
            font-size: 13px;
            margin: 0;
            color: #495057;
            font-weight: normal;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .head {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        .head td {
            vertical-align: top;
        }

        .return-number {
            font-size: 16px;
            font-weight: bold;
            text-align: right;
        }

        .meta {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        .meta td {
            width: 50%;
            border: 1px solid #dee2e6;
            padding: 8px 10px;
            vertical-align: top;
        }

        .label {
            color: #6c757d;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            font-weight: bold;
        }

        .party {
            font-size: 13px;
            font-weight: bold;
            margin: 2px 0 4px;
        }

        .muted {
            color: #6c757d;
            margin: 0;
        }

        table.lines {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        table.lines thead {
            display: table-header-group;
        }

        table.lines tr {
            page-break-inside: avoid;
        }

        table.lines th,
        table.lines td {
            border: 1px solid #dee2e6;
            padding: 5px 6px;
        }

        table.lines th {
            background: #e9ecef;
            text-align: left;
        }

        .text-end {
            text-align: right;
        }

        .sku {
            color: #6c757d;
            font-size: 10px;
        }

        .totals {
            width: 240px;
            margin-left: auto;
            border-collapse: collapse;
        }

        .totals td {
            padding: 3px 0;
        }

        .totals .grand td {
            border-top: 1px solid #212529;
            font-weight: bold;
            font-size: 13px;
            padding-top: 6px;
        }

        .notes {
            margin-top: 16px;
            border: 1px solid #dee2e6;
            padding: 8px 10px;
        }

        .notes p {
            margin: 4px 0 0;
            white-space: pre-wrap;
        }

        .status {
            margin-top: 10px;
            color: #495057;
        }
    </style>
</head>

<body>
    <table class="head">
        <tr>
            <td>
                <h1>{{ $document->businessName }}</h1>
                <h2>Purchase return</h2>
            </td>
            <td class="return-number">
                {{ $return->return_number }}
                <div class="muted">{{ $return->return_date?->format('d M Y') }}</div>
            </td>
        </tr>
    </table>

    <table class="meta">
        <tr>
            <td>
                <div class="label">Supplier</div>
                <div class="party">{{ $supplier?->name ?: 'No supplier' }}</div>
                @if ($supplier?->email)
                    <p class="muted">{{ $supplier->email }}</p>
                @endif
                @if ($supplier?->phone)
                    <p class="muted">{{ $supplier->phone }}</p>
                @endif
                @foreach ($supplierAddress as $line)
                    <p class="muted">{{ $line }}</p>
                @endforeach
            </td>
            <td>
                <div class="label">Return from</div>
                <div class="party">{{ $store?->name ?: 'No store' }}</div>
                @if ($store?->address)
                    <p class="muted">{{ $store->address }}</p>
                @endif
                <p class="muted">PO number: {{ $return->po_number ?: '—' }}</p>
                <p class="muted">Status: {{ $return->status->value }}</p>
                <p class="muted">Refund: {{ $return->refund_status->value }}</p>
            </td>
        </tr>
    </table>

    <table class="lines">
        <thead>
            <tr>
                <th style="width: 28px;">#</th>
                <th>Product</th>
                <th class="text-end" style="width: 70px;">Qty</th>
                <th class="text-end" style="width: 80px;">Unit price</th>
                <th class="text-end" style="width: 80px;">Tax</th>
                <th class="text-end" style="width: 90px;">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($return->items as $item)
                @php
                    $productName = $item->variant?->product?->name;
                    $variantName = trim((string) ($item->variant?->name ?? ''));
                    $label =
                        $productName && $variantName && $variantName !== $productName
                            ? $productName . ' · ' . $variantName
                            : ($productName ?:
                            ($variantName !== ''
                                ? $variantName
                                : ($item->variant?->sku ?:
                                'Item')));
                @endphp
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>
                        {{ $label }}
                        @if ($item->variant?->sku)
                            <div class="sku">SKU: {{ $item->variant->sku }}</div>
                        @endif
                        @if ((float) $item->discount_amount > 0)
                            <div class="sku">Discount {{ $numbers->amount($item->discount_amount) }}</div>
                        @endif
                    </td>
                    <td class="text-end">{{ $numbers->quantity($item->quantity) }}</td>
                    <td class="text-end">{{ $numbers->amount($item->unit_cost) }}</td>
                    <td class="text-end">{{ $numbers->amount($item->tax_amount) }}</td>
                    <td class="text-end">{{ $numbers->amount($item->total_amount) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="muted" style="text-align: center;">This return has no lines.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td>Subtotal</td>
            <td class="text-end">{{ $numbers->amount($return->subtotal) }}</td>
        </tr>
        <tr>
            <td>Discount</td>
            <td class="text-end">{{ $numbers->amount($return->discount_amount) }}</td>
        </tr>
        <tr>
            <td>Tax</td>
            <td class="text-end">{{ $numbers->amount($return->tax_amount) }}</td>
        </tr>
        <tr class="grand">
            <td>Grand total</td>
            <td class="text-end">{{ $numbers->amount($return->grand_total) }}</td>
        </tr>
    </table>

    @if (filled($return->reason))
        <div class="notes">
            <div class="label">Reason</div>
            <p>{{ $return->reason }}</p>
        </div>
    @endif

    @if (filled($return->notes))
        <div class="notes">
            <div class="label">Notes</div>
            <p>{{ $return->notes }}</p>
        </div>
    @endif

    <p class="status">
        Created by {{ $return->creator?->name ?: 'Unknown' }}
        · Generated {{ $document->generatedAt }}
    </p>
</body>

</html>
