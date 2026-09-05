@php
    $uiAsset = $uiAsset ?? 'vendor/ui';
@endphp

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="{{ asset($uiAsset.'/js/form.tabs.js') }}"></script>

<script>
    window.CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    window.BASE_PATH = document.querySelector('meta[name="app-url"]')?.getAttribute('content');

    $(function () {
        $('.select2, .select2_dropdown').select2({
            width: '100%',
        });
    });

    function logout() {
        var form = document.getElementById('ui-logout-form');

        if (form) {
            form.submit();
        }
    }
</script>
