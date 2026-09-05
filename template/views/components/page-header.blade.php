@props([
    'kicker' => 'Console',
    'title',
    'subtitle' => null,
])

<div {{ $attributes->class('d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4') }}>
    <div>
        <div class="admin-page-kicker">{{ $kicker }}</div>
        <h1 class="admin-page-title h3">{{ $title }}</h1>
        @if ($subtitle)
            <p class="text-secondary mb-0 mt-1">{{ $subtitle }}</p>
        @endif
    </div>
    @if (isset($actions))
        <div class="d-flex align-items-center gap-2">{{ $actions }}</div>
    @endif
</div>
