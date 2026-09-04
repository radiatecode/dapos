@props([
    'id' => $attributes['id'] ?: $attributes['name'],
    'helpBlockClass' => $attributes['block-class'] ? "help-block {$attributes['block-class']}" : 'help-block',
])

<div class="form-group {{ $id }}-form-group {{ $attributes['form-group-class'] }}">
    @if (!$attributes['no-label'])
        <label for="{{ $attributes['name'] }}">
            <i class="{{ $attributes['label-icon'] ?: 'fas fa-pen-square' }}"></i>
            <span
                id="{{ $attributes['name'] }}_label">{{ $attributes['label'] ?? str_label($attributes['name']) }}</span>
            <span id="{{ $id }}_required"
                class="required">{{ $attributes['required'] || $attributes['required-when'] ? '*' : '' }}</span>
            {!! $attributes['info']
                ? '<i class="fa fa-info-circle text-gray" title="' . $attributes['info'] . '" style="cursor: pointer"></i>'
                : '' !!}
        </label>
    @endif
    <input
        {{ $attributes->merge([
            'id' => $id,
            'type' => 'text',
            'name' => $attributes['name'],
            'class' => 'form-control cinput' . ($errors->has($attributes['name']) ? ' is-invalid' : ''),
            'placeholder' => $attributes['label'] ?? str_label($attributes['name']) . '...',
            'value' => old($attributes['name'], $attributes['default-value']),
        ]) }}
        {{ $attributes['required-when'] ? 'required' : '' }} {{ $attributes['readonly-when'] ? 'readonly' : '' }} />
    <span class="error invalid-feedback">{{ $errors->first($attributes['name']) }}</span>
    @if ($attributes['help-block'])
        <span class="{{ $helpBlockClass }}" id="{{ $id }}_help_block">{!! $attributes['help-block'] !!}</span>
    @endif
</div>
