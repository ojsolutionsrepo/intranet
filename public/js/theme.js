(function () {
    const KEY = 'oj-theme';
    const ORDER = ['system', 'light', 'dark'];

    function resolved(theme) {
        if (theme === 'dark' || theme === 'light') {
            return theme;
        }
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    function labelFor(theme) {
        if (theme === 'light') return 'Light';
        if (theme === 'dark') return 'Dark';
        return 'System';
    }

    function apply(theme) {
        const value = theme || localStorage.getItem(KEY) || 'dark';
        const mode = value === 'light' || value === 'dark' || value === 'system' ? value : 'dark';

        document.documentElement.setAttribute('data-theme', mode);
        document.documentElement.setAttribute('data-theme-resolved', resolved(mode));
        localStorage.setItem(KEY, mode);

        document.querySelectorAll('[data-theme-select]').forEach((el) => {
            if (el.value !== mode) {
                el.value = mode;
            }
        });

        document.querySelectorAll('[data-theme-cycle]').forEach((btn) => {
            btn.dataset.theme = mode;
            btn.title = 'Theme: ' + labelFor(mode) + ' (click to change)';
            btn.setAttribute('aria-label', 'Colour theme: ' + labelFor(mode));
            const label = btn.querySelector('[data-theme-label]');
            if (label) {
                label.textContent = labelFor(mode);
            }
        });
    }

    function nextTheme(current) {
        const index = ORDER.indexOf(current);
        return ORDER[(index + 1) % ORDER.length];
    }

    function bind() {
        document.querySelectorAll('[data-theme-select]').forEach((el) => {
            el.value = localStorage.getItem(KEY) || 'system';
            el.addEventListener('change', () => apply(el.value));
        });

        document.querySelectorAll('[data-theme-cycle]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const current = localStorage.getItem(KEY) || 'system';
                apply(nextTheme(current));
            });
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
