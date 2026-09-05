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

    if (typeof bindTabErrorNavigation === "function") {
        bindTabErrorNavigation($form);
    }

    $form
        .parsley()
        .on("field:validated", function () {
            let ok = $(".parsley-error").length === 0;
            $(".bs-callout-info").toggleClass("hidden", !ok);
            $(".bs-callout-warning").toggleClass("hidden", ok);

            if (typeof markErrorTabs === "function" && $form.find(".tab-pane").length) {
                markErrorTabs($form[0]);
            }
        })
        .on("form:submit", function (e) {
            if (typeof includeHiddenTabFields === "function") {
                includeHiddenTabFields($form);
            }

            if ($form.find(".tab-pane").length && $form.parsley().isValid({ force: true }) === false) {
                $form.parsley().validate({ force: true });

                if (typeof showFirstErrorTab === "function") {
                    showFirstErrorTab($form[0]);
                }

                return false;
            }

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
                        var errors =
                            error.responseJSON && error.responseJSON.errors
                                ? error.responseJSON.errors
                                : null;

                        if (errors) {
                            $.laravelErrorShow(
                                errors,
                                errorType,
                                errorWithCustomArrayIndex
                            );
                        }

                        if (typeof showFirstErrorTab === "function") {
                            showFirstErrorTab($form[0], errors);
                        }

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

    if (typeof includeHiddenTabFields === "function") {
        includeHiddenTabFields($form);
    }

    $form.parsley().validate({ force: true });

    if ($form.parsley().isValid({ force: true })) {
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
                    var clickErrors =
                        error.responseJSON && error.responseJSON.errors
                            ? error.responseJSON.errors
                            : null;

                    if (clickErrors) {
                        $.laravelErrorShow(
                            clickErrors,
                            errorType,
                            errorWithCustomArrayIndex
                        );
                    }

                    if (typeof showFirstErrorTab === "function") {
                        showFirstErrorTab($form[0], clickErrors);
                    }

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