@php
    $appName = $appName ?? config('app.name', 'Admin');
    $logoutUrl = $logoutUrl ?? url('/logout');
@endphp

<footer class="main-footer">
    <div class="float-right d-none d-sm-block">
        <b>{{ $appName }}</b> Version 1.0
    </div>
    <strong>Copyright &copy; {{ date('Y') }} {{ $appName }}.</strong> All rights reserved.
</footer>

<form id="ui-logout-form" method="POST" action="{{ $logoutUrl }}" class="d-none">
    @csrf
</form>
