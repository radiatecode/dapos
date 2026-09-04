@props([
    'id' => str_replace('[]', '', $attributes['name'])
])
<div class="form-group {{ $attributes['form-group-class'] }}">
    @if(! $attributes['no-label'])
        <label id="{{ $id }}_label" for="{{ $id }}">
            <i class="{{ $attributes['label-icon'] ?: 'fas fa-check-square' }}"></i>
            {{ $attributes['label'] ?? str_label($id) }} {!! $attributes['required'] ? '<span class="required">*</span>' : '' !!}
        </label>
    @endif
    <select {{ $attributes->merge([
    'id' => $id,
    'class'=> 'form-control select2'.($errors->has($id) ? ' is-invalid' : ''),
    ]) }}>
        {{ $slot }}
    </select>
    <span class="error invalid-feedback">{{ $errors->first($id) }}</span>
    @prepend('js')
        <script type="text/javascript">
            let _{{ $id }} = $('#{{ $id }}').select2({
                placeholder: 'Select {{ $attributes['label'] ?? str_label($id) }}...',
                width: '100%',
                allowClear: '{{ (bool) $attributes['allowclear'] }}'
            });

            _{{ $id }}.val('{{ old($id) ?? $attributes['selected'] }}').trigger('change');
        </script>
    @endprepend
</div>
