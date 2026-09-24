@prepend('css')
    <style>
        .section-container {
            margin: {{ $attributes['margin'] ?: '0px' }};
            padding: 15px;
            border: {{ $attributes['border'] ?: '1px solid #ccc' }};
            position: relative;
        }

        .section-legend {
            font-weight: bold;
            font-size: {{ $attributes['title-size'] ?: '1.1em' }};
            padding: 0 10px;
            position: absolute;
            top: -0.7em;
            left: 15px;
            background-color: {{ $attributes['title-background'] ?: 'white' }};
            color: lightslategrey;
        }
    </style>
@endprepend

<div class="section-container">
    <div class="section-legend">
        {{ $legend ?? 'Panel Name' }}
    </div>
    {{ $slot }}
</div>
