/**
 *
 * @param errors
 * @param errorType
 */
function laravelErrors(errors, errorType = 'inline', isCustomArrayIndex = false) {
    $.removeLaravelErrors();

    if (errorType === 'inline') {
        $.each(errors, function (key, value) {
            var name = (typeof laravelFieldName === 'function') ? laravelFieldName(key) : key;
            var elements = document.getElementsByName(name);

            if (elements.length === 0 && key.includes('.')) {
                var split = key.split('.');
                var array_name = split[0];
                var index = parseInt(split[1], 10);
                var nested = split[2];

                if (nested !== undefined) {
                    elements = document.getElementsByName(array_name + '[' + index + '][' + nested + ']');
                } else if (isCustomArrayIndex) {
                    elements = document.getElementsByName(array_name + '[' + index + ']');
                } else {
                    var list = document.getElementsByName(array_name + '[]');
                    elements = list[index] ? [list[index]] : [];
                }
            }

            if (elements.length > 0) {
                var element = elements[0];
                element.classList.add('is-invalid');

                var invalidFeedback = element.parentNode.querySelector('.invalid-feedback');

                if (invalidFeedback) {
                    invalidFeedback.innerHTML = value[0];
                } else {
                    element.parentNode.insertAdjacentHTML(
                        'beforeend',
                        '<span class="error invalid-feedback">' + value[0] + '</span>'
                    );
                }
            }
        });
    }

    if (errorType === 'list') {
        laravelErrorsWithList(errors);
    }

    if (errorType === 'toast') {
        laravelErrorsWithToast(errors);
    }
}

function laravelErrorsWithToast(errors) {
    // error loop
    //let [key, value] = Object.entries(errors)[0]

    toastr.error(Object.values(errors)[0][0], 'Error!', { progressBar: true, timeOut: 3000, extendedTimeOut: 5000 });

    // $.each(errors, function (key, value) {
    //     toastr.error(value[0], 'Error!', { progressBar: false, timeOut: 5000, extendedTimeOut: 6000 });
    // });
}

function laravelErrorsWithList(errors) {
    var generalErrors = '';

    // error loop
    $.each(errors, function (key, value) {
        generalErrors += '<li>' + value[0] + '</li>';
    });

    var unOrderErrorList = '<div class="alert alert-danger">\n' +
        '<ul>\n' +
        generalErrors + '\n' +
        '</ul>\n' +
        '</div>';

    $('.laravel-errors').html(unOrderErrorList);
}

/**
 * jquery error show function
 *
 * @param errors
 * @param errorType
 */
$.laravelErrorShow = function (errors, errorType = 'inline', isCustomArrayIndex = false) {
    laravelErrors(errors, errorType, isCustomArrayIndex);
}

$.laravelErrorWithToast = function (errors) {
    laravelErrorsWithToast(errors);
}

$.laravelErrorsWithList = function (errors) {
    laravelErrorsWithList(errors);
}

$.removeLaravelErrors = function () {
    $('.form-control').removeClass('is-invalid');

    $('.error').html('');

    $('.laravel-errors').html('');
}