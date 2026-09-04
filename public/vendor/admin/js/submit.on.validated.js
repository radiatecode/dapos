/**
 *
 * @param {*} formId
 * @param {*} param1 { loader = 'alert', errorType = 'inline', extraData = () => {}, callback = () => {}, redirectTo = value }
 */
function ajaxSubmitOnValidated(
    formId,
    {
        loader = "alert",
        errorType = "inline",
        errorWithCustomArrayIndex = false,
        noRedirect = false,
        extraData = null,
        callback = null,
        redirectTo = null,
    } = {}
) {
    let $form = $("#" + formId);

    $form
        .parsley()
        .on("field:validated", function () {
            let ok = $(".parsley-error").length === 0;
            $(".bs-callout-info").toggleClass("hidden", !ok);
            $(".bs-callout-warning").toggleClass("hidden", ok);
        })
        .on("form:submit", function (e) {
            $.removeLaravelErrors();

            if (callback) {
                let result = callback();

                if (!result) {
                    return false;
                }
            }

            var formData = new FormData($form[0]);

            if (extraData) {
                const extraDataResult = extraData();

                for (let key in extraDataResult) {
                    formData.append(key, extraDataResult[key]);
                }
            }

            networkRequest(loader).ajax(
                {
                    url: $form.attr("action"),
                    type: "post",
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: "json",
                },
                function (response) {
                    if (
                        response === "success" ||
                        response.status === "success"
                    ) {
                        if (redirectTo) {
                            return redirectTo();
                        }

                        if (
                            response.redirect_to !== undefined &&
                            response.redirect_to
                        ) {
                            window.location.href = response.redirect_to;
                        }

                        if (noRedirect) {
                            let message = response.message !== undefined ? response.message : 'Successfully performed the action.';

                            toastr.success(message, "Success", {
                                progressBar: true,
                            });
                        } else {
                            window.location.reload();
                        }
                    }
                },
                function (error) {
                    if (error.status === 422) {
                        $.laravelErrorShow(
                            error.responseJSON.errors,
                            errorType,
                            errorWithCustomArrayIndex
                        );

                        return;
                    }

                    let message =
                        error.responseJSON.message == undefined
                            ? error.responseJSON
                            : error.responseJSON.message;

                    toastr.error(message, "Error! " + error.status, {
                        progressBar: true,
                    });
                }
            );

            return false;
        });
}

function ajaxSubmitClickOnValidate(
    formId,
    {
        loader = "alert",
        errorType = "inline",
        errorWithCustomArrayIndex = false,
        extraData = null,
        callback = null,
        redirectTo = null,
    }
) {
    let $form = $("#" + formId);

    $form.parsley().validate();

    if ($form.parsley().isValid()) {
        $.removeLaravelErrors();

        if (callback) {
            let result = callback();

            if (!result) {
                return false;
            }
        }

        var formData = new FormData($form[0]);

        if (extraData) {
            const extraDataResult = extraData();

            for (let key in extraDataResult) {
                formData.append(key, extraDataResult[key]);
            }
        }

        networkRequest(loader).ajax(
            {
                url: $form.attr("action"),
                type: "post",
                data: formData,
                processData: false,
                contentType: false,
                dataType: "json",
            },
            function (response) {
                if (response === "success" || response.status === "success") {
                    if (
                        response.redirect_to !== undefined &&
                        response.redirect_to
                    ) {
                        window.location.href = response.redirect_to;
                    } else if (redirectTo) {
                        window.location.href = redirectTo;
                    } else {
                        window.location.reload();
                    }
                }
            },
            function (error) {
                if (error.status === 422) {
                    $.laravelErrorShow(
                        error.responseJSON.errors,
                        errorType,
                        errorWithCustomArrayIndex
                    );

                    return;
                }

                let message =
                    error.responseJSON.message !== undefined
                        ? error.responseJSON.message
                        : error.responseJSON;

                toastr.error(message, `${error.statusText}! -${error.status}`, {
                    progressBar: true,
                });
            }
        );

        return true;
    }

    return false;
}

function submitOnValidated(formId, callback = null) {
    let $form = $("#" + formId);

    $form
        .parsley()
        .on("field:validated", function () {
            let ok = $(".parsley-error").length === 0;
            $(".bs-callout-info").toggleClass("hidden", !ok);
            $(".bs-callout-warning").toggleClass("hidden", ok);
        })
        .on("form:submit", function (e) {
            if (callback) {
                let result = callback();

                return !!result;
            }

            return true;
        });
}

$.ajaxSubmitOnValidated = function (
    formId,
    {
        loader = "alert",
        errorType = "inline",
        errorWithCustomArrayIndex = false,
        noRedirect = false,
        extraData = null,
        callback = null,
        redirectTo = null,
    } = {}
) {
    ajaxSubmitOnValidated(formId, {
        loader,
        errorType,
        errorWithCustomArrayIndex,
        noRedirect,
        extraData,
        callback,
        redirectTo,
    });
};

$.submitOnValidated = function (formId, callback = null) {
    submitOnValidated(formId, callback);
};