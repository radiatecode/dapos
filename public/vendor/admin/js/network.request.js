/**
 * extend the default jquery ajax
 * customise the success & error function for loading spinner
 */
function networkRequest(loader = 'overlay', csrf = true) {
    var ajax_spinner = $('#ajax_spinner');

    var token = $('meta[name="csrf-token"]').attr('content');

    var customAjax = {};
    var defaults = {};

    customAjax.ajax = function (options, successCallback, errorCallback) {
        if (csrf) {
            defaults.headers = $.extend({}, options.headers || {}, {
                'X-CSRF-TOKEN': token,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            });
        }

        if (loader === 'overlay') {
            ajax_spinner.removeClass('hidden');
        } else if (loader === 'alert') {
            defaults.beforeSend = function () {
                Swal.fire({
                    title: 'Please Wait !',
                    html: 'processing...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading()
                    },
                });
            }
        } else if (loader === 'btn-loader') {
            $('.btn-icon').addClass('hidden');
            $('.btn-spinner').removeClass('hidden');
            $('.btn-loader').attr('disabled', true);
        }

        defaults.success = function (data, status, xhr) {  //hijack the success handler
            if (loader === 'overlay') {
                ajax_spinner.addClass('hidden');
            } else if (loader === 'alert') {
                Swal.close();
            } else if (loader === 'btn-loader') {
                $('.btn-icon').removeClass('hidden');
                $('.btn-spinner').addClass('hidden');
                $('.btn-loader').attr('disabled', false);
            }

            successCallback(data, status, xhr); // success callback function
        }
        defaults.error = function (xhr, status, error) {  //hijack the error handler
            if (loader === 'overlay') {
                ajax_spinner.addClass('hidden');
            } else if (loader === 'alert') {
                Swal.close();
            } else if (loader === 'btn-loader') {
                $('.btn-icon').removeClass('hidden');
                $('.btn-spinner').addClass('hidden');
                $('.btn-loader').attr('disabled', false);
            }

            errorCallback(xhr, status, error); // error callback
        }

        $.extend(options, defaults);  //merge passed options to defaults
        return $.ajax(options); //send request
    };


    return customAjax;
}