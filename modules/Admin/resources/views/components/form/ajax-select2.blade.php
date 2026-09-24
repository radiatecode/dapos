@props([
    'id' => str_replace('[]', '', $attributes['name']),
    'width' => $attributes['width'] ?? '100%',
    'external' => $attributes['external_id'] ?: '',
])
<div class="form-group admin-field {{ $attributes['form-group-class'] }}">
    @if (!$attributes['no-label'])
        <label id="{{ $id }}_label" for="{{ $id }}">
            <i class="{{ $attributes['label-icon'] ?: 'fas fa-check-square' }}"></i>
            {{ $attributes['label'] ?? str_label($id) }}
            <span id="{{ $id }}_required"
                class="required">{{ $attributes['required'] || $attributes['required-when'] ? '*' : '' }}</span>
            {!! $attributes['info']
                ? '<i class="fa fa-info-circle text-gray" title="' . $attributes['info'] . '" style="cursor: pointer"></i>'
                : '' !!}
        </label>
    @endif
    @if ($attributes['data-create-new'])
        <a href="{{ $attributes['data-create-new'] }}" target="_blank" class="float-right">Add New +</a>
    @endif
    <select
        {{ $attributes->merge([
            'id' => $id,
            'class' => 'form-control admin-input' . ($errors->has($id) ? ' is-invalid' : ''),
        ]) }}>
        @if (old('_' . $id . '_text'))
            <!-- for array it does not work -->
            <option value="{{ old($id) }}" selected>{{ old('_' . $id . '_text') }}</option>
        @else
            {{ $slot }}
        @endif
    </select>
    <input type="hidden" id="_{{ $id }}_text" name="_{{ $id }}_text"
        value="{{ old('_' . $id . '_text') }}">
    <input type="hidden" id="_external_{{ $id }}" name="_external_{{ $id }}"
        value="{{ old('_external_' . $id, $external) }}">

    @if ($attributes['help-block'])
        <span class="help-block">{{ $attributes['help-block'] }}</span>
    @endif
    <span class="error invalid-feedback">{{ $errors->first($id) }}</span>
</div>
@prepend('js')
    <script type="text/javascript">
        let ${{ $id }} = $('#{{ $id }}').select2({
            placeholder: 'Select {{ $attributes['label'] ?? str_label($id) }}...',
            width: '{{ $width }}',
            allowClear: '{{ (bool) $attributes['allowclear'] }}',
            ajax: {
                url: function() {
                    if ('{{ $attributes['depends-on'] }}') {
                        return '{{ $attributes['data-url'] }}'.replace(':id', $(
                            '#{{ $attributes['depends-on'] }}').val())
                    }

                    return '{{ $attributes['data-url'] }}'
                },
                data: function(params) {
                    let data = {
                        search: params.term,
                        page: params.page || 1
                    };

                    if ('{{ $attributes['params'] }}') {
                        let extraParams = '{{ $attributes['params'] }}'.split(':');

                        for (let item in extraParams) {
                            let paramElm = extraParams[item];

                            data[paramElm] = $('#' + paramElm).val()
                        }
                    }

                    return data;
                }
            }
        }).on('select2:select', function(event) {
            let selectedData = event.params.data;

            $('#_{{ $id }}_text').val(selectedData.text);

            if (selectedData.extra !== undefined && selectedData.extra.external_id !== undefined) {
                $('#_external_{{ $id }}').val(selectedData.extra.external_id);
            }
        }).on('select2:clear', function(event) {
            $('#_external_{{ $id }}').val(null);
            $('#_{{ $id }}_text').val(null);
        });
    </script>
@endprepend