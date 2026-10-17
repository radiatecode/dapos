@props([
    'id' => str_replace('[]', '', $attributes['name']),
])

<div class="form-group {{ $attributes['form-group-class'] }}">
    <div class="custom-control custom-checkbox">
        <input type="checkbox"
            {{ $attributes->merge([
                'id' => $id,
                'class' => 'custom-control-input',
                'name' => $attributes['name'],
            ]) }}
            {{ $attributes['checked-when'] ? 'checked' : '' }} {{ $attributes['disabled-when'] ? 'disabled' : '' }}>
        <label for="{{ $id }}" class="custom-control-label">{{ $attributes['label'] }}</label>
    </div>
    @if ($attributes['help-block'])
        <span class="help-block">{{ $attributes['help-block'] }}</span>
    @endif
</div>
