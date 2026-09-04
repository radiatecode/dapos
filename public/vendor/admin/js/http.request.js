/**
 * extend the default jquery ajax
 * customise the success & error function for loading spinner
 */
function httpRequest(loader = 'overlay', csrf = true) {
    var ajax_spinner = $('#ajax_spinner');

    var token = $('meta[name="csrf-token"]').attr('content');

    var request = {};
    var defaults = {};

    if (csrf) {
        defaults.headers = {
            'X-CSRF-TOKEN': token
        }
    }

    request.config = function (options, successCallback, errorCallback) {

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
        }

        defaults.success = function (data, status, xhr) {  //hijack the success handler
            if (loader === 'overlay') {
                ajax_spinner.addClass('hidden');
            } else {
                Swal.close();
            }

            successCallback(data, status, xhr); // success callback function
        }
        defaults.error = function (xhr, status, error) {  //hijack the error handler
            if (loader === 'overlay') {
                ajax_spinner.addClass('hidden');
            } else {
                Swal.close();
            }

            errorCallback(xhr, status, error); // error callback
        }

        if (options.url.toLowerCase() === 'put'){
            options.data._method = 'PUT';
        }

        $.extend(options, defaults);  //merge passed options to defaults

        return $.ajax(options); //send request
    };


    return request;
}
