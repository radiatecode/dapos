@php
    use DA\Admin\Enums\SubscriptionEventType;
    use DA\Admin\Enums\SubscriptionStatus;

    $status = $subscription->status;
    $canManage = auth('admin')->user()?->hasAdminPermission(\DA\Admin\Enums\AdminPermission::SubscriptionsManage);
    $canCreateInvoice = auth('admin')->user()?->hasAdminPermission(\DA\Admin\Enums\AdminPermission::InvoicesCreate);
    $planChanges = $subscription->events->filter(fn ($event) => $event->event_type?->isPlanChange());
@endphp

@extends('admin::app.layouts.app')

@section('title', 'Subscription '.$subscription->id)

@section('page_heading')
    Subscription details
@endsection

@section('breadcrumbs')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.subscriptions.index') }}">Subscriptions</a></li>
        <li class="breadcrumb-item active">#{{ $subscription->id }}</li>
    </ol>
@endsection

@section('content')
    <div class="tenant-page">
        <section class="tenant-hero">
            <div class="tenant-hero__body">
                <div class="tenant-avatar" aria-hidden="true">
                    <span>{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($subscription->tenant?->name ?? 'S', 0, 1)) }}</span>
                </div>

                <div class="tenant-hero__copy">
                    <p class="tenant-kicker">Subscription</p>
                    <h2 class="tenant-hero__name">{{ $subscription->tenant?->name ?? 'Tenant' }}</h2>
                    <p class="tenant-hero__slug">{{ $subscription->plan?->name }} · {{ $subscription->plan?->code }}</p>

                    <div class="tenant-chips">
                        @if ($subscription->trial_ends_at)
                            <span class="tenant-chip">
                                <i class="fas fa-hourglass-half"></i>
                                Trial until {{ $subscription->trial_ends_at->toDayDateTimeString() }}
                            </span>
                        @endif
                        @if ($subscription->current_period_start && $subscription->current_period_end)
                            <span class="tenant-chip">
                                <i class="fas fa-calendar-alt"></i>
                                {{ $subscription->current_period_start->toFormattedDateString() }}
                                –
                                {{ $subscription->current_period_end->toFormattedDateString() }}
                            </span>
                        @endif
                        @if ($subscription->cancel_at_period_end)
                            <span class="tenant-chip">
                                <i class="fas fa-ban"></i>
                                Cancels at period end
                            </span>
                        @endif
                        @if ($subscription->isInGrace())
                            <span class="tenant-chip">
                                <i class="fas fa-hourglass-half"></i>
                                Grace until {{ $subscription->grace_ends_at->toDayDateTimeString() }}
                            </span>
                        @endif
                    </div>
                </div>

                <span class="tenant-status tenant-status--{{ $status?->color() ?? 'muted' }}">
                    {{ $status?->label() ?? 'Unknown' }}
                </span>
            </div>
        </section>

        <div class="row">
            <div class="col-lg-8">
                @include('admin::app.tenants._info_section', [
                    'title' => 'Subscription',
                    'icon' => 'fas fa-sync-alt',
                    'rows' => [
                        'Tenant' => $subscription->tenant?->name,
                        'Current plan' => $subscription->plan?->name,
                        'Plan code' => $subscription->plan?->code,
                        'Status' => $status?->label(),
                        'Starts at' => $subscription->starts_at?->toDayDateTimeString(),
                        'Trial ends at' => $subscription->trial_ends_at?->toDayDateTimeString(),
                        'Period start' => $subscription->current_period_start?->toDayDateTimeString(),
                        'Period end' => $subscription->current_period_end?->toDayDateTimeString(),
                        'Grace days' => $subscription->grace_days,
                        'Grace ends at' => $subscription->grace_ends_at?->toDayDateTimeString(),
                        'Cancel at period end' => $subscription->cancel_at_period_end ? 'Yes' : 'No',
                        'Cancelled at' => $subscription->cancelled_at?->toDayDateTimeString(),
                        'Ended at' => $subscription->ended_at?->toDayDateTimeString(),
                    ],
                ])

                <div class="card admin-surface-card tenant-section mb-3">
                    <div class="card-header admin-surface-card__header tenant-section__header">
                        <span class="tenant-section__icon"><i class="fas fa-list"></i></span>
                        <h3 class="card-title mb-0">Items</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table admin-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Item</th>
                                        <th>Qty</th>
                                        <th>Unit price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($subscription->items as $item)
                                        <tr>
                                            <td>{{ $item->item_type->label() }}</td>
                                            <td>
                                                @if ($item->isPlan())
                                                    {{ $subscription->plan?->name }}
                                                @else
                                                    {{ $addons[$item->reference_id]->name ?? '#'.$item->reference_id }}
                                                @endif
                                            </td>
                                            <td>{{ $item->quantity }}</td>
                                            <td>{{ $item->unit_price }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card admin-surface-card tenant-section mb-3">
                    <div class="card-header admin-surface-card__header tenant-section__header">
                        <span class="tenant-section__icon"><i class="fas fa-puzzle-piece"></i></span>
                        <h3 class="card-title mb-0">Add-ons</h3>
                    </div>
                    <div class="card-body">
                        @php
                            $assignedAddons = $subscription->items->filter(fn ($item) => $item->isAddon());
                        @endphp

                        @if ($assignedAddons->isEmpty())
                            <p class="admin-card-hint">No add-ons are assigned to this subscription yet.</p>
                        @else
                            <div class="table-responsive mb-3">
                                <table class="table admin-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Add-on</th>
                                            <th>Qty</th>
                                            <th>Unit price</th>
                                            @if ($canManage && $subscription->isCurrent())
                                                <th></th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($assignedAddons as $item)
                                            <tr>
                                                <td>{{ $addons[$item->reference_id]->name ?? '#'.$item->reference_id }}</td>
                                                <td>
                                                    @if ($canManage && $subscription->isCurrent())
                                                        <form method="POST"
                                                            action="{{ route('admin.subscriptions.addons.update', [$subscription, $item->reference_id]) }}"
                                                            class="d-flex align-items-center" style="gap: .5rem;">
                                                            @csrf
                                                            @method('PATCH')
                                                            <input type="number" name="quantity" min="1" max="100"
                                                                class="form-control admin-input"
                                                                value="{{ $item->quantity }}" required
                                                                style="width: 5rem;"
                                                                aria-label="Quantity for {{ $addons[$item->reference_id]->name ?? 'add-on' }}">
                                                            <button type="submit" class="btn btn-sm admin-table-btn admin-table-btn-edit">
                                                                Update
                                                            </button>
                                                        </form>
                                                    @else
                                                        {{ $item->quantity }}
                                                    @endif
                                                </td>
                                                <td>{{ $item->unit_price }}</td>
                                                @if ($canManage && $subscription->isCurrent())
                                                    <td>
                                                        <form method="POST"
                                                            action="{{ route('admin.subscriptions.addons.destroy', [$subscription, $item->reference_id]) }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm admin-table-btn admin-table-btn-edit">
                                                                Remove
                                                            </button>
                                                        </form>
                                                    </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        @if ($canManage && $subscription->isCurrent() && $availableAddons->isNotEmpty())
                            <form method="POST" action="{{ route('admin.subscriptions.addons.store', $subscription) }}">
                                @csrf
                                <label class="mb-1" for="addon_id">Add add-on</label>
                                <div class="d-flex flex-wrap align-items-end" style="gap: .75rem;">
                                    <select name="addon_id" id="addon_id" class="form-control admin-input" required style="max-width: 20rem;">
                                        <option value="">Select an add-on</option>
                                        @foreach ($availableAddons as $availableAddon)
                                            <option value="{{ $availableAddon->id }}">
                                                {{ $availableAddon->name }} — {{ $availableAddon->currency?->code }} {{ $availableAddon->price }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div>
                                        <label class="mb-1" for="addon_quantity">Quantity</label>
                                        <input type="number" name="quantity" id="addon_quantity" min="1" max="100"
                                            class="form-control admin-input" value="{{ old('quantity', 1) }}" required
                                            style="width: 6rem;">
                                    </div>
                                    <button type="submit" class="btn admin-btn btn-primary">
                                        <i class="fas fa-plus"></i>
                                        Add add-on
                                    </button>
                                </div>
                            </form>
                        @elseif ($canManage && $subscription->isCurrent())
                            <p class="admin-card-hint mb-0">
                                No unused add-ons are available.
                                <a href="{{ route('admin.addons.index') }}">Create an add-on</a>
                                first, then return here to attach it.
                            </p>
                        @endif
                    </div>
                </div>

                <div class="card admin-surface-card tenant-section mb-3">
                    <div class="card-header admin-surface-card__header tenant-section__header">
                        <span class="tenant-section__icon"><i class="fas fa-exchange-alt"></i></span>
                        <h3 class="card-title mb-0">Plan change history</h3>
                    </div>
                    <div class="card-body">
                        @if ($planChanges->isEmpty())
                            <p class="admin-card-hint mb-0">No plan changes yet.</p>
                        @else
                            <div class="table-responsive">
                                <table class="table admin-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>When</th>
                                            <th>Change</th>
                                            <th>From</th>
                                            <th>To</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($planChanges as $event)
                                            <tr>
                                                <td>{{ $event->occurred_at?->toDayDateTimeString() }}</td>
                                                <td>{{ $event->event_type->label() }}</td>
                                                <td>{{ $event->metadata['old_plan_name'] ?? '—' }}</td>
                                                <td>{{ $event->metadata['new_plan_name'] ?? '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="card admin-surface-card tenant-section mb-3">
                    <div class="card-header admin-surface-card__header tenant-section__header">
                        <span class="tenant-section__icon"><i class="fas fa-bolt"></i></span>
                        <h3 class="card-title mb-0">Subscription history</h3>
                    </div>
                    <div class="card-body">
                        @if ($subscription->events->isEmpty())
                            <p class="admin-card-hint mb-0">No events recorded.</p>
                        @else
                            <div class="table-responsive">
                                <table class="table admin-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>When</th>
                                            <th>Event</th>
                                            <th>From</th>
                                            <th>To</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($subscription->events as $event)
                                            <tr>
                                                <td>{{ $event->occurred_at?->toDayDateTimeString() }}</td>
                                                <td>{{ $event->event_type->label() }}</td>
                                                <td>{{ $event->old_status?->label() ?? '—' }}</td>
                                                <td>{{ $event->new_status?->label() ?? '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="card admin-surface-card tenant-section mb-3">
                    <div class="card-header admin-surface-card__header tenant-section__header">
                        <span class="tenant-section__icon"><i class="fas fa-file-invoice"></i></span>
                        <h3 class="card-title mb-0">Billing history</h3>
                    </div>
                    <div class="card-body">
                        @if ($subscription->invoices->isEmpty())
                            <p class="text-secondary mb-0">No invoices have been generated for this subscription.</p>
                        @else
                            <div class="table-responsive">
                                <table class="table admin-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Number</th>
                                            <th>Status</th>
                                            <th>Total</th>
                                            <th>Due</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($subscription->invoices as $invoice)
                                            <tr>
                                                <td>
                                                    <a href="{{ route('admin.invoices.show', $invoice) }}">
                                                        {{ $invoice->invoice_number }}
                                                    </a>
                                                </td>
                                                <td>{{ $invoice->status->label() }}</td>
                                                <td>{{ $invoice->formattedTotal() }}</td>
                                                <td>{{ $invoice->due_date?->toFormattedDateString() }}</td>
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
                        <div class="tenant-actions__status">
                            <span>Current status</span>
                            <span class="tenant-status tenant-status--{{ $status?->color() ?? 'muted' }}">
                                {{ $status?->label() ?? 'Unknown' }}
                            </span>
                        </div>

                        @if ($canCreateInvoice && $subscription->current_period_start)
                            <form method="POST" action="{{ route('admin.invoices.store') }}" class="mb-3">
                                @csrf
                                <input type="hidden" name="subscription_id" value="{{ $subscription->id }}">
                                <label class="mb-1" for="coupon_code">Generate invoice</label>
                                <input type="text" name="coupon_code" id="coupon_code"
                                    class="form-control admin-input mb-2" placeholder="Coupon code (optional)">
                                <button type="submit" class="btn admin-btn btn-primary tenant-action-btn">
                                    <i class="fas fa-file-invoice"></i>
                                    <span>Generate invoice</span>
                                </button>
                            </form>
                        @endif

                        @if ($canManage && $subscription->isCurrent())
                            <form method="POST" action="{{ route('admin.subscriptions.grace-days', $subscription) }}" class="mb-3">
                                @csrf
                                @method('PATCH')
                                <label class="mb-1" for="grace_days">Grace days</label>
                                <input type="number" name="grace_days" id="grace_days" min="0" max="365"
                                    class="form-control admin-input mb-2"
                                    value="{{ old('grace_days', $subscription->grace_days) }}" required>
                                <p class="admin-card-hint">Access continues this many days after the period ends.</p>
                                <button type="submit" class="btn admin-btn btn-secondary tenant-action-btn">
                                    <i class="fas fa-hourglass-half"></i>
                                    <span>Update grace window</span>
                                </button>
                            </form>
                        @endif

                        @if ($canManage && in_array($status, [SubscriptionStatus::Active, SubscriptionStatus::Trialing], true))
                            <form method="POST" action="{{ route('admin.subscriptions.change-plan', $subscription) }}" class="mb-2">
                                @csrf
                                <label class="mb-1" for="plan_id">Change plan</label>
                                <select name="plan_id" id="plan_id" class="form-control admin-input mb-2" required>
                                    <option value="">Select a plan</option>
                                    @foreach ($plans as $plan)
                                        <option value="{{ $plan->id }}" @selected($plan->id === $subscription->plan_id)>
                                            {{ $plan->name }} — {{ $plan->price }}
                                        </option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn admin-btn btn-primary tenant-action-btn">
                                    <i class="fas fa-exchange-alt"></i>
                                    <span>Update plan</span>
                                </button>
                            </form>
                        @endif

                        @if ($canManage && $subscription->isCurrent() && $status !== SubscriptionStatus::Trialing && ($subscription->plan?->trial_days ?? 0) > 0)
                            <form method="POST" action="{{ route('admin.subscriptions.start-trial', $subscription) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn admin-btn btn-info tenant-action-btn">
                                    <i class="fas fa-hourglass-half"></i>
                                    <span>Start trial</span>
                                </button>
                            </form>
                        @endif

                        @if ($canManage && $status === SubscriptionStatus::Trialing)
                            <form method="POST" action="{{ route('admin.subscriptions.activate', $subscription) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn admin-btn btn-success tenant-action-btn">
                                    <i class="fas fa-check-circle"></i>
                                    <span>Activate</span>
                                </button>
                            </form>
                        @endif

                        @if ($canManage && $status === SubscriptionStatus::PastDue)
                            <form method="POST" action="{{ route('admin.subscriptions.activate', $subscription) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn admin-btn btn-success tenant-action-btn">
                                    <i class="fas fa-check-circle"></i>
                                    <span>Activate</span>
                                </button>
                            </form>
                        @endif

                        @if ($canManage && in_array($status, [SubscriptionStatus::Active, SubscriptionStatus::Trialing, SubscriptionStatus::PastDue], true))
                            <form method="POST" action="{{ route('admin.subscriptions.pause', $subscription) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn admin-btn btn-warning tenant-action-btn">
                                    <i class="fas fa-pause"></i>
                                    <span>Pause</span>
                                </button>
                            </form>
                        @endif

                        @if ($canManage && $status === SubscriptionStatus::Paused)
                            <form method="POST" action="{{ route('admin.subscriptions.resume', $subscription) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn admin-btn btn-success tenant-action-btn">
                                    <i class="fas fa-play"></i>
                                    <span>Resume</span>
                                </button>
                            </form>
                        @endif

                        @if ($canManage && $status === SubscriptionStatus::Active)
                            <form method="POST" action="{{ route('admin.subscriptions.past-due', $subscription) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn admin-btn btn-warning tenant-action-btn">
                                    <i class="fas fa-exclamation-circle"></i>
                                    <span>Mark past due</span>
                                </button>
                            </form>
                        @endif

                        @if ($canManage && $status?->isCurrent())
                            <form method="POST" action="{{ route('admin.subscriptions.cancel', $subscription) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="cancel_at_period_end" value="0">
                                <div class="custom-control custom-checkbox admin-check mb-2">
                                    <input class="custom-control-input" type="checkbox" id="cancel_at_period_end"
                                        name="cancel_at_period_end" value="1" checked>
                                    <label class="custom-control-label" for="cancel_at_period_end">Cancel at period end</label>
                                </div>
                                <button type="submit" class="btn admin-btn btn-danger tenant-action-btn">
                                    <i class="fas fa-ban"></i>
                                    <span>Cancel</span>
                                </button>
                            </form>
                        @endif

                        @if ($canManage && ! $subscription->isEnded())
                            <form method="POST" action="{{ route('admin.subscriptions.expire', $subscription) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn admin-btn btn-secondary tenant-action-btn">
                                    <i class="fas fa-clock"></i>
                                    <span>Expire</span>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
