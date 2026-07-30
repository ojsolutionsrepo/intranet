(function () {
    const KEY = 'oj-sidebar';

    function isMobile() {
        return window.matchMedia('(max-width: 767px)').matches;
    }

    function current() {
        return document.documentElement.getAttribute('data-sidebar') === 'collapsed'
            ? 'collapsed'
            : 'expanded';
    }

    function apply(state, persist) {
        const value = state === 'collapsed' ? 'collapsed' : 'expanded';
        document.documentElement.setAttribute('data-sidebar', value);
        if (persist !== false) {
            try {
                localStorage.setItem(KEY, value);
            } catch (e) {
                // ignore
            }
        }

        const expanded = value === 'expanded';
        document.querySelectorAll('[data-sidebar-toggle]').forEach((btn) => {
            btn.setAttribute('aria-expanded', expanded ? 'true' : 'false');
            if (isMobile()) {
                btn.title = expanded ? 'Close menu' : 'Open menu';
                btn.setAttribute('aria-label', expanded ? 'Close menu' : 'Open menu');
            } else {
                btn.title = expanded ? 'Collapse sidebar' : 'Expand sidebar';
                btn.setAttribute('aria-label', expanded ? 'Collapse sidebar' : 'Expand sidebar');
            }
        });
    }

    function toggle() {
        apply(current() === 'expanded' ? 'collapsed' : 'expanded');
    }

    function bind() {
        document.querySelectorAll('[data-sidebar-toggle]').forEach((btn) => {
            btn.addEventListener('click', (event) => {
                event.preventDefault();
                toggle();
            });
        });

        document.querySelectorAll('[data-sidebar-close]').forEach((el) => {
            el.addEventListener('click', () => apply('collapsed'));
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && isMobile() && current() === 'expanded') {
                apply('collapsed');
            }
        });

        window.matchMedia('(max-width: 767px)').addEventListener('change', (mq) => {
            if (mq.matches) {
                apply('collapsed', false);
            } else {
                const stored = localStorage.getItem(KEY) || 'expanded';
                apply(stored === 'collapsed' ? 'collapsed' : 'expanded', false);
            }
        });
    }

    const initial = document.documentElement.getAttribute('data-sidebar') || 'expanded';
    apply(initial, false);
    document.addEventListener('DOMContentLoaded', bind);

    window.OJSidebar = { apply, toggle };
})();
