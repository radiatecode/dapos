@php
    /** @var string $title */
    /** @var string $icon */
    /** @var array<string, mixed> $rows */
@endphp

<div class="card admin-surface-card tenant-section mb-3">
    <div class="card-header admin-surface-card__header tenant-section__header">
        <span class="tenant-section__icon">
            <i class="{{ $icon }}"></i>
        </span>
        <h3 class="card-title mb-0">{{ $title }}</h3>
    </div>
    <div class="card-body">
        <dl class="tenant-info-grid tenant-info-list mb-0">
            @foreach ($rows as $label => $value)
                <div @class(['tenant-info-item', 'is-empty' => ! filled($value)])>
                    <dt>{{ $label }}</dt>
                    <dd>{{ filled($value) ? $value : '—' }}</dd>
                </div>
            @endforeach
        </dl>
    </div>
</div>
