@props([
    'title' => null,
    'flush' => false,
])

<section {{ $attributes->class('admin-card') }}>
    @if ($title || isset($header))
        <div class="admin-card-header">
            <div>
                @if ($title)
                    <h2 class="h6 mb-0 fw-bold">{{ $title }}</h2>
                @endif
                {{ $header ?? '' }}
            </div>
            {{ $toolbar ?? '' }}
        </div>
    @endif
    <div @class(['admin-card-body', 'p-0' => $flush])>
        {{ $slot }}
    </div>
</section>
