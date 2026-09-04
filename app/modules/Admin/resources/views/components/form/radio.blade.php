@props([
    'id' => $attributes['id'] ?: $attributes['name'],
])

<div class="form-group">
    <div class="custom-control custom-radio">
        <input class="custom-control-input" type="radio" {{ $attributes->merge([
            'id' => $id,
            'name' => $attributes['name']
        ]) }} {{ $attributes['checked-when'] ? 'checked' : '' }} />
        <label for="{{ $id }}" class="custom-control-label">{{ $attributes['label'] }}</label>
    </div>
</div>

