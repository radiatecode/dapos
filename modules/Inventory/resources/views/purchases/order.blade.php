@php
    /** @var \DA\Inventory\DTO\Purchase\PurchaseOrderDocument $document */
    $purchase = $document->purchase;
    $numbers = $document->numbers;
    $supplier = $purchase->supplier;
    $store = $purchase->store;
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
    <title>Purchase Order {{ $purchase->po_number }}</title>
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

        .po-number {
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

        table.lines tfoot td {
            font-weight: bold;
            background: #f8f9fa;
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
                <h2>Purchase order</h2>
            </td>
            <td class="po-number">
                {{ $purchase->po_number }}
                <div class="muted">{{ $purchase->order_date?->format('d M Y') }}</div>
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
                <div class="label">Deliver to</div>
                <div class="party">{{ $store?->name ?: 'No store' }}</div>
                @if ($store?->address)
                    <p class="muted">{{ $store->address }}</p>
                @endif
                <p class="muted">Status: {{ $purchase->status->value }}</p>
                <p class="muted">Payment: {{ $purchase->payment_status->value }}</p>
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
            @forelse ($purchase->items as $item)
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
                    <td class="text-end">{{ $numbers->amount($item->total) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="muted" style="text-align: center;">This purchase has no lines.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td>Subtotal</td>
            <td class="text-end">{{ $numbers->amount($purchase->subtotal) }}</td>
        </tr>
        <tr>
            <td>Discount</td>
            <td class="text-end">{{ $numbers->amount($purchase->discount_amount) }}</td>
        </tr>
        <tr>
            <td>Tax</td>
            <td class="text-end">{{ $numbers->amount($purchase->tax_amount) }}</td>
        </tr>
        <tr>
            <td>Paid</td>
            <td class="text-end">{{ $numbers->amount($purchase->paid_amount) }}</td>
        </tr>
        <tr>
            <td>Due</td>
            <td class="text-end">{{ $numbers->amount($purchase->due_amount) }}</td>
        </tr>
        <tr class="grand">
            <td>Grand total</td>
            <td class="text-end">{{ $numbers->amount($purchase->grand_total) }}</td>
        </tr>
    </table>

    <h2 style="margin: 16px 0 8px;">Payment history</h2>
    <table class="lines">
        <thead>
            <tr>
                <th style="width: 110px;">Date</th>
                <th>Reference</th>
                <th class="text-end" style="width: 90px;">Amount</th>
                <th class="text-end" style="width: 90px;">Balance due</th>
            </tr>
        </thead>
        <tbody>
            @php $runningPaid = '0.0000'; @endphp
            @forelse ($purchase->payments as $payment)
                @php
                    $runningPaid = \DA\Inventory\Services\Decimal::add($runningPaid, $payment->amount);
                    $balanceDue = \DA\Inventory\Services\Decimal::sub($purchase->grand_total, $runningPaid);
                @endphp
                <tr>
                    <td>{{ $payment->paid_at?->format('d M Y') }}</td>
                    <td>
                        {{ $payment->reference_number ?: '—' }}
                        @if (filled($payment->note))
                            <div class="sku">{{ $payment->note }}</div>
                        @endif
                    </td>
                    <td class="text-end">{{ $numbers->amount($payment->amount) }}</td>
                    <td class="text-end">{{ $numbers->amount($balanceDue) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="muted" style="text-align: center;">No payment has been recorded.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if (filled($purchase->notes))
        <div class="notes">
            <div class="label">Notes</div>
            <p>{{ $purchase->notes }}</p>
        </div>
    @endif

    <p class="status">
        Created by {{ $purchase->creator?->name ?: 'Unknown' }}
        · Generated {{ $document->generatedAt }}
    </p>
</body>

</html>
