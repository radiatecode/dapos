@props([
    'id' => $attributes['id'] ?: $attributes['name'],
    'helpBlockClass' => $attributes['block-class'] ? "help-block {$attributes['block-class']}" : 'help-block',
])

@php
    $fieldLabel = $attributes['label'] ?? \Illuminate\Support\Str::of($attributes['name'] ?? '')->replace(['[', ']', '_'], ' ')->headline();
@endphp

<div class="form-group admin-field {{ $id }}-form-group {{ $attributes['form-group-class'] }}">
    @if (! $attributes['no-label'])
        <label for="{{ $id }}">
            <i class="{{ $attributes['label-icon'] ?: 'fas fa-pen-square' }}"></i>
            <span id="{{ $attributes['name'] }}_label">{{ $fieldLabel }}</span>
            <span id="{{ $id }}_required" class="required">{{ $attributes['required'] || $attributes['required-when'] ? '*' : '' }}</span>
        </label>
    @endif
    <input
        {{ $attributes->merge([
            'id' => $id,
            'type' => 'text',
            'name' => $attributes['name'],
            'class' => 'form-control cinput admin-input'.($errors->has($attributes['name']) ? ' is-invalid' : ''),
            'placeholder' => $fieldLabel.'...',
            'value' => old($attributes['name'], $attributes['default-value']),
        ]) }}
        {{ $attributes['required-when'] ? 'required' : '' }}
        {{ $attributes['readonly-when'] ? 'readonly' : '' }} />
    <span class="error invalid-feedback">{{ $errors->first($attributes['name']) }}</span>
    @if ($attributes['help-block'])
        <span class="{{ $helpBlockClass }}" id="{{ $id }}_help_block">{!! $attributes['help-block'] !!}</span>
    @endif
</div>
