import { mountPublicWebApp } from './vue/app';

/**
 * CampusHub public Web runtime.
 *
 * Vue owns stateful Web components. The small delegated layer remains only for
 * legacy shell navigation and dismissible alerts until those consumers migrate.
 */
function initializeDelegatedInteractions() {
    const root = document.documentElement;

    if (root.dataset.webDelegatedInteractions === 'ready') {
        return;
    }

    root.dataset.webDelegatedInteractions = 'ready';

    document.addEventListener('click', (event) => {
        const navToggle = event.target.closest('[data-web-nav-toggle]');

        if (navToggle) {
            toggleNavigation(navToggle);
            return;
        }

        const navPanelLink = event.target.closest('[data-web-nav-panel] a[href]');

        if (navPanelLink) {
            const panel = navPanelLink.closest('[data-web-nav-panel]');

            if (panel?.id) {
                closeNavigation(panel.id, false);
            }
        }

        const alertDismiss = event.target.closest('[data-web-alert-dismiss]');

        if (alertDismiss) {
            alertDismiss.closest('[data-web-alert]')?.remove();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape' && event.key !== 'Esc') {
            return;
        }

        document.querySelectorAll('[data-web-nav-toggle][aria-expanded="true"]').forEach((toggle) => {
            const panelId = toggle.getAttribute('aria-controls');

            panelId ? closeNavigation(panelId, true) : toggle.setAttribute('aria-expanded', 'false');
        });
    });

    if (typeof window.matchMedia === 'function') {
        const desktop = window.matchMedia('(min-width: 48rem)');

        desktop.addEventListener?.('change', (event) => {
            if (! event.matches) {
                return;
            }

            document.querySelectorAll('[data-web-nav-toggle][aria-expanded="true"]').forEach((toggle) => {
                const panelId = toggle.getAttribute('aria-controls');

                panelId ? closeNavigation(panelId, false) : toggle.setAttribute('aria-expanded', 'false');
            });
        });
    }
}

function toggleNavigation(toggle) {
    const panelId = toggle.getAttribute('aria-controls');
    const panel = panelId ? document.getElementById(panelId) : null;

    if (! panel) {
        return;
    }

    const expanded = toggle.getAttribute('aria-expanded') !== 'true';

    toggle.setAttribute('aria-expanded', String(expanded));
    panel.hidden = ! expanded;
}

function closeNavigation(panelId, restoreFocus) {
    const panel = document.getElementById(panelId);

    if (panel) {
        panel.hidden = true;
    }

    document.querySelectorAll(`[data-web-nav-toggle][aria-controls="${CSS.escape(panelId)}"]`).forEach((toggle, index) => {
        toggle.setAttribute('aria-expanded', 'false');

        if (restoreFocus && index === 0) {
            toggle.focus();
        }
    });
}

initializeDelegatedInteractions();
mountPublicWebApp();
