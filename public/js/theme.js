(function () {
    const KEY = 'oj-theme';

    function resolved(theme) {
        if (theme === 'dark' || theme === 'light') {
            return theme;
        }
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    function apply(theme) {
        const value = theme || localStorage.getItem(KEY) || 'system';
        document.documentElement.setAttribute('data-theme', value);
        document.documentElement.setAttribute('data-theme-resolved', resolved(value));
        localStorage.setItem(KEY, value);

        document.querySelectorAll('[data-theme-select]').forEach((el) => {
            if (el.value !== value) {
                el.value = value;
            }
        });
    }

    function bind() {
        document.querySelectorAll('[data-theme-select]').forEach((el) => {
            el.value = localStorage.getItem(KEY) || 'system';
            el.addEventListener('change', () => apply(el.value));
        });
    }

    apply(localStorage.getItem(KEY) || 'system');
    document.addEventListener('DOMContentLoaded', bind);

    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        const current = localStorage.getItem(KEY) || 'system';
        if (current === 'system') {
            apply('system');
        }
    });

    window.OJTheme = { apply };
})();
