(function () {
    var storageKey = "admin-theme";

    function preferredTheme() {
        try {
            var stored = localStorage.getItem(storageKey);

            if (stored === "dark" || stored === "light") {
                return stored;
            }
        } catch (error) {
            // Ignore storage access errors and keep the dark default.
        }

        return "dark";
    }

    function applyTheme(theme) {
        document.documentElement.setAttribute("data-theme", theme);
        document.documentElement.classList.toggle("dark", theme === "dark");

        if (document.body) {
            document.body.classList.toggle("dark-mode", theme === "dark");
        }

        var icon = document.getElementById("theme-mode-icon");

        if (icon) {
            icon.classList.toggle("fa-moon", theme === "light");
            icon.classList.toggle("fa-sun", theme === "dark");
        }

        var button = document.getElementById("theme-mode");

        if (button) {
            var nextLabel = theme === "dark" ? "Switch to light mode" : "Switch to dark mode";

            button.setAttribute("aria-pressed", theme === "dark" ? "true" : "false");
            button.setAttribute("title", nextLabel);
            button.setAttribute("aria-label", nextLabel);
        }
    }

    window.AdminTheme = {
        apply: applyTheme,
        current: function () {
            return document.documentElement.getAttribute("data-theme") || preferredTheme();
        },
        toggle: function () {
            var next = window.AdminTheme.current() === "dark" ? "light" : "dark";

            try {
                localStorage.setItem(storageKey, next);
            } catch (error) {
                // Theme still applies for this page even if storage is blocked.
            }

            applyTheme(next);
        },
    };

    applyTheme(preferredTheme());

    document.addEventListener("DOMContentLoaded", function () {
        applyTheme(window.AdminTheme.current());

        var button = document.getElementById("theme-mode");

        if (button) {
            button.addEventListener("click", function (event) {
                event.preventDefault();
                window.AdminTheme.toggle();
            });
        }
    });
})();
