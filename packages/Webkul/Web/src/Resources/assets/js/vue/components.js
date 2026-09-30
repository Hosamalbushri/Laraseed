import WebAccordion from './components/WebAccordion';
import WebModal from './components/WebModal';
import WebDrawer from './components/WebDrawer';
import WebDropdown from './components/WebDropdown';

/**
 * Explicit registration keeps the public runtime deterministic and auditable.
 */
export function registerPublicWebComponents(app) {
    app.component('v-web-accordion', WebAccordion);
    app.component('v-web-modal', WebModal);
    app.component('v-web-drawer', WebDrawer);
    app.component('v-web-dropdown', WebDropdown);
}
