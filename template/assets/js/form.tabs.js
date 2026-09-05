function laravelFieldName(key) {
    var parts = String(key).split(".");

    return (
        parts[0] +
        parts
            .slice(1)
            .map(function (part) {
                return "[" + part + "]";
            })
            .join("")
    );
}

function fieldByName(form, name) {
    if (!name) {
        return null;
    }

    var fields = form.querySelectorAll("[name]");

    for (var i = 0; i < fields.length; i++) {
        if (fields[i].getAttribute("name") === name) {
            return fields[i];
        }
    }

    var matches = document.getElementsByName(name);

    return matches.length ? matches[0] : null;
}

function firstInvalidField(form) {
    return (
        form.querySelector("input.parsley-error, select.parsley-error, textarea.parsley-error") ||
        form.querySelector("input.is-invalid, select.is-invalid, textarea.is-invalid") ||
        form.querySelector(".parsley-error, .is-invalid")
    );
}

function firstFieldFromErrors(form, errors) {
    if (!errors) {
        return null;
    }

    var keys = Object.keys(errors);

    for (var i = 0; i < keys.length; i++) {
        var field = fieldByName(form, laravelFieldName(keys[i]));

        if (field) {
            return field;
        }
    }

    return null;
}

function tabTriggerForPane(form, pane) {
    if (!pane || !pane.id) {
        return null;
    }

    var selector = '[data-bs-target="#' + pane.id + '"], [href="#' + pane.id + '"]';

    return form.querySelector(selector) || document.querySelector(selector);
}

function markErrorTabs(form) {
    var triggers = form.querySelectorAll('[data-bs-toggle="tab"], [data-toggle="tab"]');

    if (!triggers.length) {
        triggers = document.querySelectorAll('[data-bs-toggle="tab"], [data-toggle="tab"]');
    }

    triggers.forEach(function (tab) {
        tab.classList.remove("text-danger");
    });

    form.querySelectorAll(".tab-pane").forEach(function (pane) {
        if (!pane.querySelector(".parsley-error, .is-invalid")) {
            return;
        }

        var tab = tabTriggerForPane(form, pane);

        if (tab) {
            tab.classList.add("text-danger");
        }
    });
}

function showTabPane(form, pane) {
    if (!pane) {
        return;
    }

    var trigger = tabTriggerForPane(form, pane);

    if (trigger && window.bootstrap && window.bootstrap.Tab) {
        window.bootstrap.Tab.getOrCreateInstance(trigger).show();
        return;
    }

    form.querySelectorAll(".tab-pane").forEach(function (el) {
        el.classList.remove("active", "show");
    });

    var navLinks = form.querySelectorAll(".nav-tabs .nav-link");

    if (!navLinks.length) {
        navLinks = document.querySelectorAll(".nav-tabs .nav-link");
    }

    navLinks.forEach(function (el) {
        el.classList.remove("active");
        el.setAttribute("aria-selected", "false");
    });

    pane.classList.add("active", "show");

    if (trigger) {
        trigger.classList.add("active");
        trigger.setAttribute("aria-selected", "true");
    }
}

function activateTabForField(form, field) {
    if (!field) {
        return;
    }

    var pane = field.closest(".tab-pane");

    if (pane) {
        showTabPane(form, pane);
    }

    window.setTimeout(function () {
        if (typeof field.focus === "function") {
            field.focus();
        }
    }, 150);
}

function showFirstErrorTab(form, errors) {
    var formEl = typeof form === "string" ? document.querySelector(form) : form;

    if (!formEl || !formEl.querySelector(".tab-pane")) {
        return;
    }

    markErrorTabs(formEl);
    activateTabForField(
        formEl,
        firstFieldFromErrors(formEl, errors) || firstInvalidField(formEl)
    );
}

function includeHiddenTabFields(form) {
    var $form = $(form);
    var excluded = "input[type=hidden], [disabled]";

    if (window.Parsley && window.Parsley.options) {
        window.Parsley.options.excluded = excluded;
    }

    var parsley = $form.parsley({ excluded: excluded });

    parsley.options.excluded = excluded;

    if (typeof parsley.refresh === "function") {
        parsley.refresh();
    }

    return parsley;
}

function formHasInvalidFields(form) {
    var $form = $(form);
    var parsley = includeHiddenTabFields($form);

    if (typeof parsley.validate === "function") {
        return parsley.validate({ force: true }) === false;
    }

    return parsley.validate() === false;
}

function bindTabErrorNavigation(form) {
    var $form = $(form);
    var formEl = $form[0];

    if (!formEl || $form.find(".tab-pane").length === 0) {
        return;
    }

    if (formEl.dataset.tabErrorsBound === "1") {
        includeHiddenTabFields($form);
        return;
    }

    formEl.dataset.tabErrorsBound = "1";
    $form.attr("novalidate", "novalidate");

    var parsley = includeHiddenTabFields($form);

    formEl.addEventListener(
        "submit",
        function (event) {
            if (!formHasInvalidFields(formEl)) {
                return;
            }

            event.preventDefault();
            event.stopImmediatePropagation();
            showFirstErrorTab(formEl);
        },
        true
    );

    $form.on("click.tabErrors", '[type="submit"]', function (event) {
        if (!formHasInvalidFields(formEl)) {
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();
        showFirstErrorTab(formEl);

        return false;
    });

    parsley.on("form:error", function () {
        window.setTimeout(function () {
            showFirstErrorTab(formEl);
        }, 0);
    });
}

function initTabbedFormValidation(formId) {
    var $form = $(formId);

    if (!$form.length) {
        return;
    }

    $form.attr("novalidate", "novalidate");
    bindTabErrorNavigation($form);
}
