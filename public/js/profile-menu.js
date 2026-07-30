(function () {
    function closeAll(except) {
        document.querySelectorAll('[data-profile-menu]').forEach((menu) => {
            if (except && menu === except) {
                return;
            }
            const panel = menu.querySelector('[data-profile-menu-panel]');
            const toggle = menu.querySelector('[data-profile-menu-toggle]');
            if (panel) {
                panel.hidden = true;
            }
            if (toggle) {
                toggle.setAttribute('aria-expanded', 'false');
            }
            menu.classList.remove('is-open');
        });
    }

    function bind() {
        document.querySelectorAll('[data-profile-menu]').forEach((menu) => {
            const toggle = menu.querySelector('[data-profile-menu-toggle]');
            const panel = menu.querySelector('[data-profile-menu-panel]');
            if (!toggle || !panel) {
                return;
            }

            toggle.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                const willOpen = panel.hidden;
                closeAll();
                if (willOpen) {
                    panel.hidden = false;
                    toggle.setAttribute('aria-expanded', 'true');
                    menu.classList.add('is-open');
                }
            });
        });

        document.addEventListener('click', () => closeAll());
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeAll();
            }
        });
    }

    document.addEventListener('DOMContentLoaded', bind);
})();
