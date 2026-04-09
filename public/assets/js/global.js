document.addEventListener("DOMContentLoaded", function () {
    const navbarToggler = document.querySelector(".navbar-toggler");
    let menuOpenIcon = document.getElementById("menu-open-icon");
    let menuCloseIcon = document.getElementById("menu-close-icon");

    if (!navbarToggler || !menuOpenIcon || !menuCloseIcon) {
        return;
    }

    navbarToggler.addEventListener("click", function () {
        if (menuOpenIcon.classList.contains("d-none")) {
            menuOpenIcon.classList.remove("d-none");
            menuCloseIcon.classList.add("d-none");
        } else {
            menuOpenIcon.classList.add("d-none");
            menuCloseIcon.classList.remove("d-none");
        }
    });
});

document.addEventListener("DOMContentLoaded", function () {
    const lightbox = document.getElementById("lightbox");
    if (lightbox) {
        document.body.classList.add("no-scroll");
    }
});

document.addEventListener("contextmenu", function(event) {
    event.preventDefault();
});

document.addEventListener("DOMContentLoaded", function () {
    const themeSwitcher = document.getElementById("theme-switcher");
    const themeReset = document.getElementById("theme-reset");
    const themeIcon = document.getElementById("theme-icon");
    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
    if (themeReset) {
        themeReset.addEventListener("click", function () {
            clearUserTheme();
            setTheme(mediaQuery.matches ? "dark" : "light");
        });
    }

    function setTheme(theme) {
        if (theme === "dark") {
            document.documentElement.setAttribute("data-theme", "dark");
            document.documentElement.setAttribute("data-bs-theme", "dark");
            themeIcon.classList.remove("bi-moon");
            themeIcon.classList.add("bi-sun");
        } else {
            document.documentElement.setAttribute("data-theme", "light");
            document.documentElement.setAttribute("data-bs-theme", "light");
            themeIcon.classList.remove("bi-sun");
            themeIcon.classList.add("bi-moon");
        }
    }

    function getUserTheme() {
        return localStorage.getItem("theme");
    }

    function setUserTheme(theme) {
        localStorage.setItem("theme", theme);
    }

    function clearUserTheme() {
        localStorage.removeItem("theme");
    }

    function applyTheme() {
        const userTheme = getUserTheme();
        if (userTheme) {
            setTheme(userTheme);
        } else {
            setTheme(mediaQuery.matches ? "dark" : "light");
        }
    }

    applyTheme();

    if (themeSwitcher) {
        themeSwitcher.addEventListener("click", function () {
            let currentTheme = document.documentElement.getAttribute("data-theme");
            let newTheme = currentTheme === "dark" ? "light" : "dark";
            setTheme(newTheme);
            setUserTheme(newTheme);
            themeSwitcher.blur();
        });
    }

    if (themeReset) {
        themeReset.addEventListener("click", function () {
            clearUserTheme();
            setTheme(mediaQuery.matches ? "dark" : "light");
            themeReset.blur();
        });
    }

    mediaQuery.addEventListener("change", function (e) {
        if (!getUserTheme()) {
            setTheme(e.matches ? "dark" : "light");
        }
    });
});
