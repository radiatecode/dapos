function preloadingAjax(options) {
    const {
        title = "Confirmation",
        text = "Are you sure?",
        icon = "info",
        confirmText = "Yes, Do It",
        ajax: { url, data = null, type = "GET" },
        redirectTo = null,
    } = options;

    let ajaxOptions = {
        url: url,
        type: type,
        dataType: "json",
        headers: {
            "X-CSRF-TOKEN": window.CSRF_TOKEN,
        },
    };

    if (data) {
        ajaxOptions.data = data;
    }

    Swal.fire({
        title: title,
        text: text,
        icon: icon,
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: confirmText,
        showLoaderOnConfirm: true,
        preConfirm: async function () {
            await $.ajax(ajaxOptions)
                .done(function (response) {
                    return response;
                })
                .fail(function (error) {
                    Swal.close();

                    let message =
                        error.responseJSON.message === undefined
                            ? error.responseJSON
                            : error.responseJSON.message;

                    toastr.error(message, "Error! -" + error.status, {
                        progressBar: true,
                    });

                    return;
                });
        },
        backdrop: true,
        allowOutsideClick: () => !Swal.isLoading(),
    }).then(function (result) {
        if (result.value) {
            if (redirectTo) {
                window.location.href = redirectTo;
            } else {
                window.location.reload();
            }
        }
    });
}
