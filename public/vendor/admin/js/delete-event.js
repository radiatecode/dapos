"use strict";
(function ($, window, i) {
    $.fn.getSelector = function () {
        return $(this).data('selector');
    };

    $.fn.delete = function () {
        var selector = $(this).getSelector();

        if (selector !== undefined)
        {
            $(document).on("click", selector, function ()
            {
                var url = $(this).data('delete-url');

                if (url === undefined)
                {
                    Swal.fire({
                        title: 'Attribute Missing !',
                        text: 'Add data-delete-url attribute to the deletable button',
                        icon: 'error'
                    });
                } else {
                    deleteAjax({
                        url: url,
                        type: "DELETE",
                    });
                }

            });
        }
    };

    $.fn.bulkDelete = function (url = null)
    {
        var selector = $(this).getSelector();

        if (selector !== undefined) {
            $(document).on("click", selector, function ()
            {
                var values = checkBoxValues();

                var options = {};

                if(! url){
                    options.url = $(this).data('delete-url');
                }

                if (values.length !== 0) {
                    options.type = "POST";

                    options.data = {selected_items: values};

                    deleteAjax(options);
                } else {
                    Swal.fire({
                        title: 'No Items Are Selected!',
                        text: 'you need to select the items to delete',
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

        let title = "You want to delete selected "

        Swal.fire({
            title: 'Are you sure?',
            text: options.type === 'POST' ? title + "items?" : title + "item?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, Delete it!',
            showLoaderOnConfirm: true,
            preConfirm: async function () {
                await $.ajax(ajaxOptions)
                    .done(function (response) {return response;})
                    .fail(function (error) {
                        Swal.close();

                        if ($.inArray(error.status,[400,401,403,404,406]) !== -1) {
                            Swal.fire({
                                title: 'Error! - '+error.statusText,
                                text: error.responseJSON.message ? error.responseJSON.message : error.responseJSON,
                                icon: 'error',
                            });

                            return;
                        }

                        Swal.fire({
                            title: 'Error! ' + error.status,
                            text: 'Something went wrong!',
                            type: 'error',
                        });
                        console.error(error);
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
