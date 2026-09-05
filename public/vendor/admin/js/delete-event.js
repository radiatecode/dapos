"use strict";
(function ($, window, i) {
    $.fn.getSelector = function () {
        return $(this).data('selector');
    };

    $.fn.delete = function (text = 'You want to delete selected items', confirmText = 'Yes, Delete It!') {
        var selector = $(this).getSelector();

        if (selector !== undefined) {
            $(document).on("click", selector, function () {
                var url = $(this).data('delete-url');

                if (url === undefined) {
                    Swal.fire({
                        title: 'Attribute Missing !',
                        text: 'Add data-delete-url attribute to the deletable button',
                        icon: 'error'
                    });
                } else {
                    deleteAjax({
                        url: url,
                        type: "DELETE",
                        text: text,
                        confirmText: confirmText
                    });
                }

            });
        }
    };

    $.fn.bulkDelete = function (url = null, text = 'You want to delete selected items', confirmText = 'Yes, Delete It!') {
        var selector = $(this).getSelector();

        if (selector !== undefined) {
            $(document).on("click", selector, function () {
                var values = checkBoxValues();

                var options = {
                    text: text,
                    confirmText: confirmText
                };

                if (!url) {
                    options.url = $(this).data('delete-url');
                }

                if (values.length !== 0) {
                    options.type = "POST";

                    options.data = { selected_rows: values };

                    deleteAjax(options);
                } else {
                    Swal.fire({
                        title: 'No Items Are Selected!',
                        text: 'you need to select the items first.',
                        icon: 'warning'
                    });
                }
            });
        }
    };

    let deleteAjax = function (options) {
        let ajaxOptions = {
            url: options.url,
            type: options.type,
            dataType: 'json',
            headers: {
                'X-CSRF-TOKEN': window.CSRF_TOKEN
            }
        }

        if (options.type === 'POST') {
            ajaxOptions.data = options.data;
        }

        Swal.fire({
            title: 'Are you sure?',
            text: options.text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: options.confirmText,
            showLoaderOnConfirm: true,
            preConfirm: async function () {
                await $.ajax(ajaxOptions)
                    .done(function (response) { return response; })
                    .fail(function (error) {
                        Swal.close();

                        if (error.status >= 400 && error.status < 500) {
                            toastr.error(error.responseJSON.message, `${error.statusText}- ${error.status}`, {
                                progressBar: true
                            });

                            return;
                        }

                        if (error.status >= 500) {
                            toastr.error(error.responseJSON.message, `${error.statusText}- ${error.status}`, {
                                progressBar: true
                            });

                            return;
                        }

                        toastr.error('Something went wrong!', `${error.statusText}- ${error.status}`, {
                            progressBar: true
                        });
                    });
            },
            backdrop: true,
            allowOutsideClick: () => !Swal.isLoading()
        }).then(function (result) {
            if (result.value) {
                window.location.reload();
            }
        });
    }

    let checkBoxValues = function () {
        var bulkIds = 'input[name="bulkIds[]"]';

        var arr = $(bulkIds + ':checked').map(function () {
            return this.value; // $(this).val()
        }).get();

        return arr;
    }

})(jQuery, this, 0);