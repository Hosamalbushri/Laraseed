# 12. Web Component Kernel, Presentation, and Interaction Rules

## Status
Mandatory Foundation Architectural Rule.

---

## 1. Fundamental Principle: Web Component Ownership and Separation

1. **Ownership**: All public Web UI components belong exclusively to `Webkul\Web` (under `packages/Webkul/Web/src/Resources/views/components/`).
2. **Admin Isolation**: Public Web components must NEVER reference, extend, or include `<x-admin::*>`, `admin::*`, `Webkul\Admin`, or Admin Vue/Vite assets.
3. **Business Package Isolation**: Web components are generic UI primitives and must NEVER reference `Webkul\Student`, `Webkul\Event`, `Webkul\LostAndFound`, or `Webkul\Shop`.
4. **No Business Logic**: Components must NEVER perform database queries (`Model::query()`, `DB::`), repository operations, or domain authorization checks (`bouncer()`, `hasPermission()`).
5. **No Routing Ownership**: Components must NEVER hardcode domain routes. Action URLs are passed via props or slots.

---

## 2. Component vs. Theme Separation of Concerns

```text
PAGE (Composition & Domain Content)
  ↓
WEB COMPONENT (Semantic HTML, Accessibility, Behavior & State Contract)
  ↓
THEME (Visual Presentation, Tokens, Colors, Typography, Spacing)
```

1. **Web Component Responsibilities**:
   - Semantic HTML structure (`<button>`, `<article>`, `<label>`, `<input>`, `<aside>`, dialog overlays).
   - Accessible ARIA contracts (`aria-expanded`, `aria-controls`, `aria-describedby`, `aria-invalid`, `role="region|alert|status|dialog"`, `aria-modal="true"`).
   - Collision-safe DOM IDs.
   - Behavior hooks (`data-web-accordion`, `data-web-modal`, `data-web-drawer`, `data-web-dropdown`, `data-web-alert`).
   - Controlled variant resolution and fallback.
2. **Theme Responsibilities**:
   - Visual tokens (colors, borders, radius, shadow, typography, spacing).
   - Component presentation styling via CSS classes or structural Blade overrides at `{themeViewsPath}/overrides/web/components/*`.
   - Theme overrides MUST NOT alter the behavioral `data-web-*` hooks or ARIA contracts.
3. **Admin Immunity**:
   - `ThemeViewFinder` strictly protects `admin`, `mail`, `notifications`, and `errors` namespaces. Themes can NEVER hijack Admin views.

---

## 3. Interaction and JavaScript Runtime Rules

1. **Approved Runtime**: Vue 3 is the approved state and interaction runtime for generic public Web primitives. It must enhance Blade-rendered pages and must not introduce a SPA, client-side routing, a global store, or a separate frontend application.
2. **One Public Application**: The Web layout owns one compiler-capable Vue application root. Components are registered once through an explicit registry; `createApp()` per component, filesystem-scanned registration, and uncontrolled global mutable state are forbidden.
3. **Blade Public API**: Website and other consumers use `<x-web::*>`. Blade owns semantic structure, slots, safe server data, and the no-JavaScript result; Vue implementation tags remain encapsulated by Web components.
4. **Meaningful State Only**: Static Button, Card, Badge, Field, and Input components remain server-rendered. Vue is used only where a primitive owns meaningful client state (Accordion, Modal, Drawer, Dropdown).
5. **Progressive Enhancement**: All components must render readable and semantic HTML server-side. Missing or disabled JS must never hide essential content, break navigation, or crash the DOM. Mounting must preserve unrelated Blade content and must not be described as SSR hydration.
6. **Idempotence and Inline Handler Ban**: Public runtime initialization must be idempotent. Zero per-instance inline event handlers (`onclick="..."`) are allowed.
7. **Behavior Selectors Independence**:
   - JavaScript targets `data-web-*` attributes ONLY.
   - JavaScript MUST NEVER use CSS class names as selectors, ensuring Themes can freely restyle or rename classes without breaking interactions.
8. **Build and Dependency Boundary**: Vite owns public frontend compilation. Web runtime code must not import Website, Student, LostAndFound, or other optional business packages. Vue Router, Pinia, Inertia, Nuxt, and third-party component frameworks require separate explicit approval.

---

## 4. Accessibility (a11y) and RTL/LTR Direction Rules

1. **Native Semantics First**: Always use native semantic elements over `<div>` or ARIA hacks (e.g. `<button>` instead of `<div role="button">`).
2. **ARIA by Contract**: Use ARIA attributes only where necessary to express dynamic relationships or state.
3. **Bidirectional Support (RTL/LTR)**:
   - Components must support both RTL and LTR seamlessly without separate language-specific components.
   - Direction authority stems strictly from `WebContextContract` (`$webContext->direction()`).
   - CSS must prefer logical properties (`margin-inline`, `padding-inline`, `inset-inline`) or bidirectional classes (`ltr:left-0 rtl:right-0`).

---

## 5. Approved Initial Primitive Set

Only the following primitives are permitted in the kernel:
1. `Button` (`<x-web::button>`)
2. `Card` (`<x-web::card>`, `<x-web::card.header>`, `<x-web::card.content>`, `<x-web::card.footer>`)
3. `Badge` (`<x-web::badge>`)
4. `Alert` (`<x-web::alert>`)
5. `Field` (`<x-web::form.field>`)
6. `Input` (`<x-web::form.input>`)
7. `Accordion` (`<x-web::accordion>`, `<x-web::accordion.item>`)
8. `Modal` (`<x-web::modal>`)
9. `Drawer` (`<x-web::drawer>`)
10. `Dropdown` (`<x-web::dropdown>`)

New components require explicit architectural justification. Speculative component authoring is strictly forbidden.
