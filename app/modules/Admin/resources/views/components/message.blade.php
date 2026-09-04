@if (Session::has('success'))
    <div class="alert alert-success" role="alert">
        <strong><i class="fa fa-check"></i> </strong>{{ Session::get('success')}}
    </div>
@endif
@if (Session::has('failed'))
    <div class="alert alert-danger" role="alert">
        <strong><i class="fa fa-exclamation"></i> </strong>{{ Session::get('failed')}}
    </div>
@endif