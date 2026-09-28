/**
 * CampusHub Web Interaction Kernel
 * Progressive enhancement and event delegation for public web components.
 * Zero external dependencies, idempotent, accessible, reduced-motion aware.
 */
(function (window, document) {
    'use strict';

    if (window.__campusHubWebInteractionsInitialized) {
        return;
    }
    window.__campusHubWebInteractionsInitialized = true;

    // Delegated click handler on document for maximum performance and minimal memory footprint
    document.addEventListener('click', function (event) {
        // 1. Accordion Trigger
        const trigger = event.target.closest('[data-web-accordion-trigger]');
        if (trigger) {
            handleAccordionToggle(trigger);
            return;
        }

        // 2. Alert Dismiss
        const alertDismiss = event.target.closest('[data-web-alert-dismiss]');
        if (alertDismiss) {
            handleAlertDismiss(alertDismiss);
            return;
        }
    });

    /**
     * Handle accordion item toggle with single/multi open support and ARIA synchronization.
     *
     * @param {HTMLElement} trigger
     */
    function handleAccordionToggle(trigger) {
        const panelId = trigger.getAttribute('aria-controls');
        if (!panelId) return;

        const panel = document.getElementById(panelId);
        if (!panel) return;

        const isExpanded = trigger.getAttribute('aria-expanded') === 'true';
        const accordion = trigger.closest('[data-web-accordion]');
        const allowMultiple = accordion && accordion.getAttribute('data-web-accordion-always-open') === 'true';

        // If single-open accordion, collapse other open items in the same container
        if (!allowMultiple && !isExpanded && accordion) {
            const activeTriggers = accordion.querySelectorAll('[data-web-accordion-trigger][aria-expanded="true"]');
            activeTriggers.forEach(function (activeTrigger) {
                if (activeTrigger !== trigger) {
                    activeTrigger.setAttribute('aria-expanded', 'false');
                    const activePanelId = activeTrigger.getAttribute('aria-controls');
                    const activePanel = activePanelId ? document.getElementById(activePanelId) : null;
                    if (activePanel) {
                        activePanel.hidden = true;
                    }
                }
            });
        }

        const nextState = !isExpanded;
        trigger.setAttribute('aria-expanded', String(nextState));
        panel.hidden = !nextState;
    }

    /**
     * Handle dismissing alert notification banner.
     *
     * @param {HTMLElement} dismissBtn
     */
    function handleAlertDismiss(dismissBtn) {
        const alertBox = dismissBtn.closest('[data-web-alert]');
        if (alertBox) {
            alertBox.remove();
        }
    }
})(window, document);
