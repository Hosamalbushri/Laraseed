export default {
    name: 'WebModal',

    props: {
        id: {
            type: String,
            required: true,
        },
    },

    data() {
        return {
            isOpen: false,
            previouslyFocusedElement: null,
        };
    },

    mounted() {
        this.isOpen = this.modalElement()?.getAttribute('data-web-modal-open') === 'true';
        this.syncState(false);
        this.$el.dataset.webVueComponent = 'modal';

        document.addEventListener('click', this.onDocumentClick);
    },

    beforeUnmount() {
        document.removeEventListener('click', this.onDocumentClick);
        if (this.isOpen) {
            this.restoreDocumentScroll();
        }
    },

    methods: {
        modalElement() {
            return this.$el.querySelector(`[data-web-modal-dialog]`);
        },

        triggers() {
            return Array.from(document.querySelectorAll(`[data-web-modal-trigger="${CSS.escape(this.id)}"]`));
        },

        focusableElements() {
            const modal = this.modalElement();
            if (! modal) return [];

            const selector = 'button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';
            return Array.from(modal.querySelectorAll(selector))
                .filter((el) => ! el.hasAttribute('disabled') && el.getAttribute('aria-hidden') !== 'true');
        },

        open(triggerElement = null) {
            if (this.isOpen) return;

            this.previouslyFocusedElement = triggerElement || document.activeElement;
            this.isOpen = true;
            this.syncState(true);

            this.$nextTick(() => {
                const focusables = this.focusableElements();
                const initialFocus = this.modalElement()?.querySelector('[data-web-modal-autofocus]') || focusables[0] || this.modalElement();
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
            const modal = this.modalElement();
            if (! modal) return;

            modal.setAttribute('data-web-modal-open', String(this.isOpen));
            modal.setAttribute('aria-hidden', String(! this.isOpen));
            modal.hidden = ! this.isOpen;

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
            const trigger = event.target.closest(`[data-web-modal-trigger="${CSS.escape(this.id)}"]`);
            if (trigger) {
                event.preventDefault();
                event.stopPropagation();
                this.toggle(trigger);
                return;
            }

            if (this.isOpen && (event.target.hasAttribute('data-web-modal-backdrop') || event.target.hasAttribute('data-web-modal-overlay'))) {
                this.close();
                return;
            }

            if (this.isOpen && event.target.closest('[data-web-modal-close]')) {
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
