@props([
    'modal_id' => $attributes['name'] ?: 'form-modal',
    'content',
])

<div class="modal fade" tabindex="-1" role="dialog" id="{{ $modal_id }}">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header border-top-yellow-light">
                <h5 class="modal-title">{{ $attributes['title'] }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ $content->attributes['action'] ?: '#' }}" method="{{ $content->attributes['method'] ?: 'post' }}"  id="{{ $modal_id }}-form" data-parsley-validate="">
                <div class="modal-body">
                    {{ $content }}
                </div>
                <div class="modal-footer">
                    <button type="{{ $attributes['submit-type'] ?: 'submit' }}" class="btn btn-outline-primary"><i
                            class="fas fa-save"></i> {{ $attributes['submit-title'] }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>