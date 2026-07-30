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

        document.querySelectorAll('[data-sidebar-toggle]').forEach((btn) => {
            const expanded = value === 'expanded';
            btn.setAttribute('aria-expanded', expanded ? 'true' : 'false');
            if (btn.classList.contains('sidebar-collapse-btn')) {
                btn.title = expanded ? 'Collapse sidebar' : 'Expand sidebar';
                const glyph = btn.querySelector('[aria-hidden="true"]');
                if (glyph) {
                    glyph.textContent = expanded ? '‹' : '›';
                }
            } else {
                btn.title = expanded ? 'Close menu' : 'Open menu';
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

        // Keep desktop preference; default mobile closed on resize into mobile.
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
