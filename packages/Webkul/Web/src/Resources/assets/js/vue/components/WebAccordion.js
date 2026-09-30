export default {
    name: 'WebAccordion',

    data() {
        return {
            expandedPanelIds: [],
        };
    },

    mounted() {
        this.expandedPanelIds = this.triggers()
            .filter((trigger) => trigger.getAttribute('aria-expanded') === 'true')
            .map((trigger) => trigger.getAttribute('aria-controls'))
            .filter(Boolean);

        this.synchronizeDom();
        this.$el.dataset.webVueComponent = 'accordion';
    },

    methods: {
        triggers() {
            return Array.from(this.$el.querySelectorAll('[data-web-accordion-trigger]'));
        },

        onClick(event) {
            const trigger = event.target.closest('[data-web-accordion-trigger]');

            if (! trigger || ! this.$el.contains(trigger)) {
                return;
            }

            this.toggle(trigger);
        },

        onKeydown(event) {
            const trigger = event.target.closest('[data-web-accordion-trigger]');

            if (! trigger || ! this.$el.contains(trigger)) {
                return;
            }

            if ((event.key === 'Escape' || event.key === 'Esc') && trigger.getAttribute('aria-expanded') === 'true') {
                event.preventDefault();
                this.setExpanded(trigger, false);
                trigger.focus();
                return;
            }

            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                this.toggle(trigger);
                return;
            }

            const triggers = this.triggers();
            const currentIndex = triggers.indexOf(trigger);
            let nextIndex = null;

            if (event.key === 'ArrowDown') nextIndex = (currentIndex + 1) % triggers.length;
            if (event.key === 'ArrowUp') nextIndex = (currentIndex - 1 + triggers.length) % triggers.length;
            if (event.key === 'Home') nextIndex = 0;
            if (event.key === 'End') nextIndex = triggers.length - 1;

            if (nextIndex !== null) {
                event.preventDefault();
                triggers[nextIndex]?.focus();
            }
        },

        toggle(trigger) {
            const willExpand = trigger.getAttribute('aria-expanded') !== 'true';
            const allowsMultiple = this.$el.getAttribute('data-web-accordion-always-open') === 'true';

            if (willExpand && ! allowsMultiple) {
                this.triggers().forEach((candidate) => {
                    if (candidate !== trigger) this.setExpanded(candidate, false, false);
                });
            }

            this.setExpanded(trigger, willExpand);
        },

        setExpanded(trigger, expanded, synchronize = true) {
            const panelId = trigger.getAttribute('aria-controls');

            if (! panelId) {
                return;
            }

            const next = new Set(this.expandedPanelIds);

            expanded ? next.add(panelId) : next.delete(panelId);
            this.expandedPanelIds = Array.from(next);

            if (synchronize) this.synchronizeDom();
        },

        synchronizeDom() {
            const expanded = new Set(this.expandedPanelIds);

            this.triggers().forEach((trigger) => {
                const panelId = trigger.getAttribute('aria-controls');
                const panel = panelId ? this.$el.querySelector(`#${CSS.escape(panelId)}`) : null;
                const isExpanded = Boolean(panelId && expanded.has(panelId));

                trigger.setAttribute('aria-expanded', String(isExpanded));

                if (panel) panel.hidden = ! isExpanded;
            });
        },
    },

    template: `
        <div @click="onClick" @keydown="onKeydown">
            <slot />
        </div>
    `,
};
