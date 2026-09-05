@props([
    'id' => str_replace('[]', '', $attributes['name']),
])

@php
    $fieldLabel = $attributes['label'] ?? \Illuminate\Support\Str::of($id)->replace(['[', ']', '_'], ' ')->headline();
@endphp

<div class="form-group admin-field {{ $attributes['form-group-class'] }}">
    @if (! $attributes['no-label'])
        <label id="{{ $id }}_label" for="{{ $id }}">
            <i class="{{ $attributes['label-icon'] ?: 'fas fa-check-square' }}"></i>
            {{ $fieldLabel }} {!! $attributes['required'] ? '<span class="required">*</span>' : '' !!}
        </label>
    @endif
    <select {{ $attributes->merge([
        'id' => $id,
        'class' => 'form-control select2 admin-input'.($errors->has($id) ? ' is-invalid' : ''),
    ]) }}>
        {{ $slot }}
    </select>
    <span class="error invalid-feedback">{{ $errors->first($id) }}</span>
    @prepend('js')
        <script>
            $('#{{ $id }}').select2({
                placeholder: 'Select {{ $fieldLabel }}...',
                width: '100%',
                allowClear: {{ $attributes['allowclear'] ? 'true' : 'false' }}
            }).val(@json(old($id, $attributes['selected']))).trigger('change');
        </script>
    @endprepend
</div>
