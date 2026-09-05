@foreach ($items as $item)
    @if (($item['type'] ?? 'item') === 'header')
        <li class="nav-header">{{ $item['label'] }}</li>
    @elseif (($item['children'] ?? []) !== [])
        <li class="nav-item has-treeview{{ $item['is_open'] ? ' menu-open' : '' }}">
            <a href="{{ $item['href'] }}" class="nav-link{{ $item['is_active'] ? ' active' : '' }}">
                <i class="nav-icon {{ $item['icon'] }}"></i>
                <p>
                    {{ $item['label'] }}
                    <i class="right fas fa-angle-left"></i>
                </p>
            </a>
            <ul class="nav nav-treeview">
                @include('admin::app.partials._nav_items', ['items' => $item['children']])
            </ul>
        </li>
    @else
        <li class="nav-item">
            <a href="{{ $item['href'] }}" class="nav-link{{ $item['is_active'] ? ' active' : '' }}">
                <i class="nav-icon {{ $item['icon'] }}"></i>
                <p>{{ $item['label'] }}</p>
            </a>
        </li>
    @endif
@endforeach
