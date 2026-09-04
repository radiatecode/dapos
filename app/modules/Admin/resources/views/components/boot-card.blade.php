@props(['title' => null])

<div {{ $attributes->class('card border-top-green-light card-outline') }}>
    <div class="card-header">
        <h3 class="card-title">
            {{ $title }}
        </h3>
        {{ $header ?? '' }}
    </div>
    <div class="card-body">
        {{ $slot }}
    </div>
    <div class="card-footer">
        {{ $footer ?? '' }}
    </div>
</div>
