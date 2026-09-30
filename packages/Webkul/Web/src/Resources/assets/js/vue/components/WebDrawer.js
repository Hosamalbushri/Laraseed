export default {
    name: 'WebDrawer',

    props: {
        id: {
            type: String,
            required: true,
        },
        placement: {
            type: String,
            default: 'start',
        },
    },

    data() {
        return {
            isOpen: false,
            previouslyFocusedElement: null,
        };
    },

    mounted() {
        this.isOpen = this.drawerElement()?.getAttribute('data-web-drawer-open') === 'true';
        this.syncState(false);
        this.$el.dataset.webVueComponent = 'drawer';

        document.addEventListener('click', this.onDocumentClick);
    },

    beforeUnmount() {
        document.removeEventListener('click', this.onDocumentClick);
        if (this.isOpen) {
            this.restoreDocumentScroll();
        }
    },

    methods: {
        drawerElement() {
            return this.$el.querySelector(`[data-web-drawer-dialog]`);
        },

        triggers() {
            return Array.from(document.querySelectorAll(`[data-web-drawer-trigger="${CSS.escape(this.id)}"]`));
        },

        focusableElements() {
            const drawer = this.drawerElement();
            if (! drawer) return [];

            const selector = 'button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';
            return Array.from(drawer.querySelectorAll(selector))
                .filter((el) => ! el.hasAttribute('disabled') && el.getAttribute('aria-hidden') !== 'true');
        },

        open(triggerElement = null) {
            if (this.isOpen) return;

            this.previouslyFocusedElement = triggerElement || document.activeElement;
            this.isOpen = true;
            this.syncState(true);

            this.$nextTick(() => {
                const focusables = this.focusableElements();
                const initialFocus = this.drawerElement()?.querySelector('[data-web-drawer-autofocus]') || focusables[0] || this.drawerElement();
                initialFocus?.focus();
            });
        },

        close() {
            if (! this.isOpen) return;

            this.isOpen = false;
            this.syncState(true);

            if (this.previouslyFocusedElement && typeof this.previouslyFocusedElement.focus === 'function') {
                this.previouslyFocusedElement.focus();
            }
            this.previouslyFocusedElement = null;
        },

        toggle(triggerElement = null) {
            this.isOpen ? this.close() : this.open(triggerElement);
        },

        syncState(animate = true) {
            const drawer = this.drawerElement();
            if (! drawer) return;

            drawer.setAttribute('data-web-drawer-open', String(this.isOpen));
            drawer.setAttribute('aria-hidden', String(! this.isOpen));
            drawer.hidden = ! this.isOpen;

            this.triggers().forEach((trigger) => {
                trigger.setAttribute('aria-expanded', String(this.isOpen));
            });

            if (this.isOpen) {
                document.documentElement.classList.add('overflow-hidden');
            } else {
                this.restoreDocumentScroll();
            }
        },

        restoreDocumentScroll() {
            const anyOtherOpenModal = document.querySelector('[data-web-modal-dialog][data-web-modal-open="true"]');
            const anyOtherOpenDrawer = document.querySelector('[data-web-drawer-dialog][data-web-drawer-open="true"]');

            if (! anyOtherOpenModal && ! anyOtherOpenDrawer) {
                document.documentElement.classList.remove('overflow-hidden');
            }
        },

        onDocumentClick(event) {
            const trigger = event.target.closest(`[data-web-drawer-trigger="${CSS.escape(this.id)}"]`);
            if (trigger) {
                event.preventDefault();
                event.stopPropagation();
                this.toggle(trigger);
                return;
            }

            if (this.isOpen && (event.target.hasAttribute('data-web-drawer-backdrop') || event.target.hasAttribute('data-web-drawer-overlay'))) {
                this.close();
                return;
            }

            if (this.isOpen && event.target.closest('[data-web-drawer-close]')) {
                this.close();
            }
        },

        onKeydown(event) {
            if (! this.isOpen) return;

            if (event.key === 'Escape' || event.key === 'Esc') {
                event.preventDefault();
                event.stopPropagation();
                this.close();
                return;
            }

            if (event.key === 'Tab') {
                this.trapFocus(event);
            }
        },

        trapFocus(event) {
            const focusables = this.focusableElements();
            if (focusables.length === 0) {
                event.preventDefault();
                return;
            }

            const first = focusables[0];
            const last = focusables[focusables.length - 1];

            if (event.shiftKey) {
                if (document.activeElement === first || ! this.$el.contains(document.activeElement)) {
                    event.preventDefault();
                    last.focus();
                }
            } else {
                if (document.activeElement === last || ! this.$el.contains(document.activeElement)) {
                    event.preventDefault();
                    first.focus();
                }
            }
        },
    },

    template: `
        <div @keydown="onKeydown">
            <slot />
        </div>
    `,
};
