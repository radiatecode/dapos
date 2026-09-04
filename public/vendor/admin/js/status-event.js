"use strict";
(function ($, window, i) {
    $.fn.getSelector = function () {
        return $(this).data('selector');
    };

    $.fn.status = function (status, type = 'single', url = null) {
        var selector = $(this).getSelector();

        if (selector !== undefined) {
            $(document).on("click", selector, function () {
                var url = url ? url : $(this).data('url');

                var items = [];

                if (type === 'single' || type === 'individual') {
                    items.push($(this).data('id'));
                }

                if (type === 'multiple' || type === 'bulk') {
                    items = checkBoxValues()
                }

                if (items.length == 0) {
                    Swal.fire({
                        title: 'No Items Are Selected!',
                        text: 'you need to select the items to ' + status,
                        icon: 'warning'
                    });

                    return;
                }

                if (url === undefined) {
                    Swal.fire({
                        title: 'URL Missing !',
                        text: 'Add data-url attribute to the approve button or status function',
                        icon: 'error'
                    });
                } else {
                    hitApi({
                        url: url,
                        type: "POST",
                        data: {
                            items: items,
                            status: status
                        },
                        confirm_title: 'You want to approve the selected item?'
                    });
                }

            });
        }
    };

    let hitApi = function (options) {
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

        let title = options.confirm_title

        Swal.fire({
            title: 'Are you sure?',
            text: title,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Confirmed!',
            backdrop: true,
            showLoaderOnConfirm: true,
            preConfirm: async function () {
                await $.ajax(ajaxOptions)
                    .done(function (response) { return response; })
                    .fail(function (error) {
                        Swal.close();
                        if (error.status === 404) {
                            Swal.fire({
                                title: 'Error!',
                                text: 'Data not found!',
                                icon: 'error',
                            });

                            return;
                        }

                        if (error.status === 422) {
                            $.laravelErrorWithToast(error.responseJSON.errors);

                            return;
                        }

                        Swal.fire({
                            title: 'Error! ' + error.status,
                            text: 'Something went wrong!',
                            icon: 'error',
                        });
                        console.error(error);
                    });
            },
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
