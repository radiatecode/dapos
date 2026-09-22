@php
    use DA\Admin\Enums\AdminPermission;
    use DA\Admin\Enums\InvoiceStatus;
    use DA\Admin\Enums\PaymentMethod;
    use DA\Admin\Enums\PaymentStatus;

    $status = $invoice->status;
    $canManage = auth('admin')->user()?->hasAdminPermission(AdminPermission::InvoicesManage);
@endphp

@extends('admin::app.layouts.app')

@section('title', $invoice->invoice_number)

@section('page_heading')
    Invoice details
@endsection

@section('breadcrumbs')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.invoices.index') }}">Invoices</a></li>
        <li class="breadcrumb-item active">{{ $invoice->invoice_number }}</li>
    </ol>
@endsection

@section('content')
    <div class="tenant-page">
        <section class="tenant-hero">
            <div class="tenant-hero__body">
                <div class="tenant-avatar" aria-hidden="true">
                    <span>{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($invoice->tenant?->name ?? 'I', 0, 1)) }}</span>
                </div>

                <div class="tenant-hero__copy">
                    <p class="tenant-kicker">Invoice</p>
                    <h2 class="tenant-hero__name">{{ $invoice->invoice_number }}</h2>
                    <p class="tenant-hero__slug">{{ $invoice->tenant?->name }} · {{ $invoice->subscription?->plan?->name }}</p>
                </div>

                <span class="tenant-status tenant-status--{{ $status?->color() ?? 'muted' }}">
                    {{ $status?->label() ?? 'Unknown' }}
                </span>
            </div>
        </section>

        <div class="row">
            <div class="col-lg-8">
                @include('admin::app.tenants._info_section', [
                    'title' => 'Invoice',
                    'icon' => 'fas fa-file-invoice',
                    'rows' => [
                        'Tenant' => $invoice->tenant?->name,
                        'Subscription' => '#'.$invoice->subscription_id,
                        'Period' => $invoice->billing_period_start?->toFormattedDateString().' – '.$invoice->billing_period_end?->toFormattedDateString(),
                        'Due date' => $invoice->due_date?->toFormattedDateString(),
                        'Subtotal' => trim(($invoice->currency?->code ?? '').' '.$invoice->subtotal),
                        'Discount' => trim(($invoice->currency?->code ?? '').' '.$invoice->discount_amount),
                        'Tax' => trim(($invoice->currency?->code ?? '').' '.$invoice->tax_amount),
                        'Total' => $invoice->formattedTotal(),
                        'Paid at' => $invoice->paid_at?->toDayDateTimeString(),
                    ],
                ])

                <div class="card admin-surface-card tenant-section mb-3">
                    <div class="card-header admin-surface-card__header tenant-section__header">
                        <span class="tenant-section__icon"><i class="fas fa-list"></i></span>
                        <h3 class="card-title mb-0">Line items</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table admin-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Description</th>
                                        <th>Qty</th>
                                        <th>Unit</th>
                                        <th>Discount</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($invoice->items as $item)
                                        <tr>
                                            <td>{{ $item->item_type->label() }}</td>
                                            <td>{{ $item->description }}</td>
                                            <td>{{ $item->quantity }}</td>
                                            <td>{{ $item->unit_price }}</td>
                                            <td>{{ $item->discount_amount }}</td>
                                            <td>{{ $item->total_amount }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card admin-surface-card tenant-section mb-3">
                    <div class="card-header admin-surface-card__header tenant-section__header">
                        <span class="tenant-section__icon"><i class="fas fa-credit-card"></i></span>
                        <h3 class="card-title mb-0">Payments</h3>
                    </div>
                    <div class="card-body">
                        @if ($invoice->payments->isEmpty())
                            <p class="text-secondary mb-0">No payments have been recorded.</p>
                        @else
                            <div class="table-responsive">
                                <table class="table admin-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Status</th>
                                            <th>Amount</th>
                                            <th>Method</th>
                                            <th>Transaction</th>
                                            <th>Paid at</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($invoice->payments as $payment)
                                            <tr>
                                                <td>{{ $payment->status->label() }}</td>
                                                <td>{{ trim(($invoice->currency?->code ?? '').' '.$payment->amount) }}</td>
                                                <td>{{ $payment->payment_method->label() }}</td>
                                                <td>{{ $payment->transaction_id }}</td>
                                                <td>{{ $payment->paid_at?->toDayDateTimeString() ?? '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card admin-surface-card tenant-actions">
                    <div class="card-header admin-surface-card__header">
                        <h3 class="card-title">Actions</h3>
                    </div>
                    <div class="card-body">
                        @if ($canManage && ($invoice->isOpen() || $invoice->isFailed()))
                            <form method="POST" action="{{ route('admin.invoices.mark-paid', $invoice) }}" class="mb-3">
                                @csrf
                                <label class="mb-1" for="paid_payment_method">Mark paid</label>
                                <select name="payment_method" id="paid_payment_method" class="form-control admin-input mb-2" required>
                                    @foreach ($paymentMethods as $method)
                                        <option value="{{ $method->value }}">{{ $method->label() }}</option>
                                    @endforeach
                                </select>
                                <input type="text" name="transaction_id" class="form-control admin-input mb-2"
                                    placeholder="Transaction ID (optional)">
                                <button type="submit" class="btn admin-btn btn-success tenant-action-btn">
                                    <i class="fas fa-check-circle"></i>
                                    <span>Mark paid</span>
                                </button>
                            </form>
                        @endif

                        @if ($canManage && $invoice->isOpen())
                            <form method="POST" action="{{ route('admin.invoices.mark-failed', $invoice) }}">
                                @csrf
                                <input type="hidden" name="payment_method" value="{{ PaymentMethod::Manual->value }}">
                                <button type="submit" class="btn admin-btn btn-danger tenant-action-btn">
                                    <i class="fas fa-times-circle"></i>
                                    <span>Mark failed</span>
                                </button>
                            </form>
                        @endif

                        <a href="{{ route('admin.subscriptions.show', $invoice->subscription_id) }}"
                            class="btn admin-btn btn-secondary tenant-action-btn mt-2">
                            <i class="fas fa-sync-alt"></i>
                            <span>View subscription</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
