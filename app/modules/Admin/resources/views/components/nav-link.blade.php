@props([
    'href' => null,
    'icon' => 'bi-circle',
    'active' => false,
    'soon' => false,
])

@if ($soon || blank($href))
    <span {{ $attributes->class(['admin-nav-link', 'is-soon' => true]) }}>
        <i class="bi {{ $icon }}"></i>
        <span>{{ $slot }}</span>
        <span class="admin-soon">Soon</span>
    </span>
@else
    <a href="{{ $href }}" {{ $attributes->class(['admin-nav-link', 'is-active' => $active]) }}>
        <i class="bi {{ $icon }}"></i>
        <span>{{ $slot }}</span>
    </a>
@endif
