(function () {
    const KEY = 'oj-theme';
    const SCHEME_KEY = 'oj-theme-scheme';
    const ORDER = ['dark', 'light', 'system'];

    function migrateScheme() {
        if (localStorage.getItem(SCHEME_KEY) !== 'glass-dark-v2') {
            if (!localStorage.getItem(KEY)) {
                localStorage.setItem(KEY, 'dark');
            }
            localStorage.setItem(SCHEME_KEY, 'glass-dark-v2');
        }
    }

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
        migrateScheme();
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
        return ORDER[(index < 0 ? 0 : index + 1) % ORDER.length];
    }

    function bind() {
        document.querySelectorAll('[data-theme-select]').forEach((el) => {
            el.value = localStorage.getItem(KEY) || 'dark';
            el.addEventListener('change', () => apply(el.value));
        });

        document.querySelectorAll('[data-theme-cycle]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const current = localStorage.getItem(KEY) || 'dark';
                apply(nextTheme(current));
            });
        });
    }

    migrateScheme();
    apply(localStorage.getItem(KEY) || 'dark');
    document.addEventListener('DOMContentLoaded', bind);

    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        const current = localStorage.getItem(KEY) || 'dark';
        if (current === 'system') {
            apply('system');
        }
    });

    window.OJTheme = { apply };
})();
