@props(['title' => null])

<div {{ $attributes->class(['card', 'admin-surface-card', 'border-top-green-light', 'card-outline']) }}>
    <div class="card-header admin-surface-card__header">
        <div>
            <h3 class="card-title">{{ $title }}</h3>
            {{ $header ?? '' }}
        </div>
    </div>
    <div class="card-body">
        {{ $slot }}
    </div>
    <div class="card-footer admin-card-actions">
        {{ $footer ?? '' }}
    </div>
</div>
