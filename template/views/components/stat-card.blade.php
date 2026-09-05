@props([
    'label',
    'value',
    'hint' => null,
    'icon' => 'fas fa-bolt',
])

<div {{ $attributes->class('card admin-surface-card admin-stat') }}>
    <div class="d-flex align-items-center gap-2 text-secondary">
        <i class="{{ $icon }}"></i>
        <span class="fw-semibold">{{ $label }}</span>
    </div>
    <div class="value">{{ $value }}</div>
    @if ($hint)
        <div class="small text-secondary">{{ $hint }}</div>
    @endif
</div>
