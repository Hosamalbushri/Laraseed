export default {
    name: 'WebDropdown',

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
        this.isOpen = this.trigger()?.getAttribute('aria-expanded') === 'true';
        this.syncState();
        this.$el.dataset.webVueComponent = 'dropdown';

        document.addEventListener('click', this.onDocumentClick);
    },

    beforeUnmount() {
        document.removeEventListener('click', this.onDocumentClick);
    },

    methods: {
        trigger() {
            return this.$el.querySelector(`[data-web-dropdown-trigger]`);
        },

        menu() {
            return this.$el.querySelector(`[data-web-dropdown-menu]`);
        },

        menuItems() {
            const menu = this.menu();
            if (! menu) return [];

            const selector = 'button:not([disabled]), a[href], [tabindex]:not([tabindex="-1"])';
            return Array.from(menu.querySelectorAll(selector))
                .filter((el) => ! el.hasAttribute('disabled') && el.getAttribute('aria-hidden') !== 'true');
        },

        open() {
            if (this.isOpen) return;

            this.previouslyFocusedElement = document.activeElement;
            this.isOpen = true;
            this.syncState();

            this.$nextTick(() => {
                const items = this.menuItems();
                if (items.length > 0) {
                    items[0].focus();
                }
            });
        },

        close(restoreFocus = true) {
            if (! this.isOpen) return;

            this.isOpen = false;
            this.syncState();

            if (restoreFocus && this.previouslyFocusedElement && typeof this.previouslyFocusedElement.focus === 'function') {
                this.previouslyFocusedElement.focus();
            } else if (restoreFocus && this.trigger()) {
                this.trigger().focus();
            }
            this.previouslyFocusedElement = null;
        },

        toggle() {
            this.isOpen ? this.close() : this.open();
        },

        syncState() {
            const trigger = this.trigger();
            const menu = this.menu();

            if (trigger) {
                trigger.setAttribute('aria-expanded', String(this.isOpen));
            }

            if (menu) {
                menu.setAttribute('data-web-dropdown-open', String(this.isOpen));
                menu.hidden = ! this.isOpen;
            }
        },

        onTriggerClick(event) {
            const trigger = event.target.closest('[data-web-dropdown-trigger]');
            if (trigger && this.$el.contains(trigger)) {
                event.preventDefault();
                event.stopPropagation();
                this.toggle();
            }
        },

        onMenuClick(event) {
            const closeTarget = event.target.closest('[data-web-dropdown-close], a[href], button');
            if (closeTarget) {
                this.close(false);
            }
        },

        onDocumentClick(event) {
            if (! this.$el.contains(event.target)) {
                this.close(false);
            }
        },

        onKeydown(event) {
            if (event.key === 'Escape' || event.key === 'Esc') {
                if (this.isOpen) {
                    event.preventDefault();
                    event.stopPropagation();
                    this.close(true);
                }
                return;
            }

            if (! this.isOpen && (event.key === 'ArrowDown' || event.key === 'Enter' || event.key === ' ')) {
                const trigger = event.target.closest('[data-web-dropdown-trigger]');
                if (trigger && this.$el.contains(trigger)) {
                    event.preventDefault();
                    this.open();
                    return;
                }
            }

            if (this.isOpen) {
                const items = this.menuItems();
                const currentIndex = items.indexOf(document.activeElement);

                if (event.key === 'ArrowDown') {
                    event.preventDefault();
                    const next = (currentIndex + 1) % items.length;
                    items[next]?.focus();
                } else if (event.key === 'ArrowUp') {
                    event.preventDefault();
                    const prev = (currentIndex - 1 + items.length) % items.length;
                    items[prev]?.focus();
                } else if (event.key === 'Home') {
                    event.preventDefault();
                    items[0]?.focus();
                } else if (event.key === 'End') {
                    event.preventDefault();
                    items[items.length - 1]?.focus();
                } else if (event.key === 'Tab') {
                    this.close(false);
                }
            }
        },
    },

    template: `
        <div class="relative inline-block text-start" @click="onTriggerClick" @keydown="onKeydown">
            <slot />
        </div>
    `,
};
