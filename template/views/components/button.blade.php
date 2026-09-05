@props([
    'class' => $attributes['class'] ?: 'btn-primary',
    'text' => $attributes['text'] ?? 'Save',
    'icon' => $attributes['icon'] ?? 'fas fa-save',
])

<button class="btn admin-btn {{ $class }} btn-loader" {{ $attributes->merge(['type' => 'submit']) }}>
    <i class="btn-icon {{ $icon }}"></i>
    <span class="btn-spinner spinner-border spinner-border-sm hidden" role="status" aria-hidden="true"></span>
    <span class="btn-text">{{ $text }}</span>
</button>
