@props([
    'type' => 'info',
])

@php
    $class = match ($type) {
        'success' => 'alert-success',
        'danger' => 'alert-danger',
        'warning' => 'alert-warning',
        default => 'alert-primary',
    };
@endphp

<div {{ $attributes->merge(['class' => 'alert '.$class.' border-0 shadow-sm', 'role' => 'alert']) }}>
    {{ $slot }}
</div>
