@props([
    'id' => str_replace('[]', '', $attributes['name']),
])

<div class="form-group">
    <div class="custom-control custom-checkbox">
        <input class="custom-control-input" type="checkbox" {{ $attributes->merge([
            'id' => $id,
            'name' => $attributes['name']
        ]) }} {{ $attributes['checked-when'] ? 'checked' : '' }}>
        <label for="{{ $id }}" class="custom-control-label">{{ $attributes['label'] }}</label>
    </div>
</div>
