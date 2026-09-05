@php
    $fieldLabel = $attributes['label'] ?? \Illuminate\Support\Str::of($attributes['name'] ?? '')->replace(['[', ']', '_'], ' ')->headline();
@endphp

<div class="form-group admin-field {{ $attributes['form-group-class'] }}">
    @if (! $attributes['no-label'])
        <label id="{{ $attributes['name'] }}_label" for="{{ $attributes['name'] }}">
            <i class="{{ $attributes['label-icon'] ?: 'fas fa-pen-square' }}"></i>
            {{ $fieldLabel }}
            {!! $attributes['required'] ? '<span class="required">*</span>' : '' !!}
        </label>
    @endif
    <textarea
        {{ $attributes->merge([
            'id' => $attributes['name'],
            'name' => $attributes['name'],
            'class' => 'form-control admin-input'.($errors->has($attributes['name']) ? ' is-invalid' : ''),
            'placeholder' => $fieldLabel.'...',
        ]) }}>{{ old($attributes['name'], $attributes['default-value']) }}</textarea>
    <span class="error invalid-feedback">{{ $errors->first($attributes['name']) }}</span>
    @if ($attributes['help-block'])
        <span class="help-block">{!! $attributes['help-block'] !!}</span>
    @endif
</div>
