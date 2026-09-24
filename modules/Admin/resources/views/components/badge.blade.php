@props([
    'tone' => 'muted',
])

@php
    $class = match ($tone) {
        'success' => 'admin-badge-success',
        'accent' => 'admin-badge-accent',
        default => 'admin-badge-muted',
    };
@endphp

<span {{ $attributes->class(['admin-badge', $class]) }}>{{ $slot }}</span>
