/**
 *
 * @param errors
 * @param errorType
 */
function laravelErrors(errors, errorType = 'inline', isCustomArrayIndex = false) {
    // remove previous error message
    $.removeLaravelErrors();

    // error loop
    if (errorType === 'inline') {

        $.each(errors, function (key, value) {
            if (key.includes('.')) { // validation multiple array input element
                var split = key.split('.');

                var array_name = split[0];
                var index = parseInt(split[1]);
                var name = split[2];

                if (name !== undefined) {
                    var element = document.getElementsByName(array_name + '[' + index + '][' + name + ']');

                    var parent = element[0].parentNode;

                    element[0].classList.add("is-invalid");

                    var invalidFeedback = parent.querySelector(".invalid-feedback");

                    if (invalidFeedback) {
                        invalidFeedback.innerHTML = value[0]; // value.join(' ') [to show all errors for this key]
                    } else {
                        parent.innerHTML += '<span class="error invalid-feedback">' + value[0] + '</span>';
                    }
                } else { // it is for non-multilevel array element
                    let element;
                    let parent;

                    if (isCustomArrayIndex) {
                        element = document.getElementsByName(array_name + `[${index}]`);
                        parent = element[0].parentNode;
                        element[0].classList.add("is-invalid");
                    } else {
                        element = document.getElementsByName(array_name + '[]');
                        element = element[index];
                        parent = element.parentNode;
                        element.classList.add("is-invalid");
                    }

                    var invalidFeedback = parent.querySelector(".invalid-feedback");

                    if (invalidFeedback) {
                        invalidFeedback.innerHTML = value[0]; // value.join(' ') [to show all errors for this key]
                    } else {
                        parent.innerHTML += '<span class="error invalid-feedback">' + value[0] + '</span>';
                    }
                }



            } else {
                //TODO: it doesn't work for array elements ex: employee_id[], in future try to use getElementById to fix it or think new approch
                let singleElm = document.getElementsByName(key);

                if (singleElm.length > 0) {
                    singleElm[0].classList.add("is-invalid");

                    let invalid = singleElm[0].parentNode.querySelector(".invalid-feedback");

                    if (invalid) {
                        invalid.innerHTML = value[0]; // value.join(' ') [to show all errors for this key]
                    } else {
                        singleElm[0].parentNode.innerHTML += '<span class="error invalid-feedback">' + value[0] + '</span>';
                    }
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