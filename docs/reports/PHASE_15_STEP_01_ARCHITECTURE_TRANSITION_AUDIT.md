# CampusFind — Phase 15 Step 01: Architecture Transition Contract & Migration Audit
**Document ID:** `PHASE_15_STEP_01_ARCHITECTURE_TRANSITION_AUDIT.md`  
**Date:** 2026-10-01  
**Author:** Principal Laravel & Package Architecture Auditor  
**Scope:** Forensic Audit, Package Dependency Mapping, Reusable Component Architecture, Frontend Build Strategy, and Target Transition Contract  
**Execution Rule:** Forensic Audit & Architecture Contract ONLY — Zero Production Source Modifications

---

## 1. Executive Summary

This forensic audit establishes the exact architectural contract and physical migration authority required to transition CampusHub/CampusFind from its legacy Theme-based architecture (`Webkul/Theme` and `themes/base`) to a clean, decoupled **Presentation Package Architecture** (`Webkul/Web` as the reusable Foundation UI Kernel and `Webkul/Website` as the optional Presentation Package).

The audit confirms:
1. **The Theme Engine is Obsolete**: The application no longer requires multiple runtime themes, theme inheritance chains, or dynamic theme switching. Maintaining `ThemeRegistry`, `ThemeResolver`, `ThemeViewFinder`, and `theme.json` introduces runtime overhead and architectural ambiguity.
2. **Current Dependency Footprint**:
   - `Webkul/Theme` consists of **15 PHP files** providing theme registration, resolution, manifest parsing, inheritance cycle validation, and view resolution overrides (`ThemeViewFinder`).
   - `themes/base` consists of **10 files** providing the current Vite build configuration (`vite.config.js`), PostCSS configuration, Tailwind configuration, CSS design tokens (`assets/css/theme.css`), and 4 Blade view overrides.
   - `Webkul/Web` contains **6 direct references** to `Webkul\Theme` across 2 files (`ResolveWebLocale` middleware and `WebServiceProvider`), while `WebContext` maintains an `activeTheme()` accessor.
   - `Webkul/Website` contains **0 direct references** to `Webkul\Theme` or `themes/base`, but depends on `themes/base` indirectly for all compiled CSS, Tailwind utility scanning, and master layout delivery.
   - Root application contains **92 references** across `bootstrap/providers.php` (1), `package.json` (2), and test suites (89).
3. **Pristine Boundary Enforcements**:
   - `Web → Website`: **0** references.
   - `Web → Student`: **0** references.
   - `Web → LostAndFound`: **0** references.
   - `Student → LostAndFound`: **0** references.
   - `LostAndFound → Website`: **0** references.
   - `Student → Website`: **0** references.
4. **Current Test Baseline**:
   - PHP Unit & Feature tests: **608 passed, 3,932 assertions** (duration: 21.98s).
   - Composer validation: **Valid**.
   - Configuration caching: **Operational**.
   - Frontend build (`npm run build`): **Passes cleanly** in 1.83s.
5. **Theme Removal Readiness**: **`THEME_REMOVAL_READY=NO`** with **7 identified architectural blockers**. A sequenced 5-step migration plan is defined to resolve every blocker deterministically before Theme is removed in later phases.

---

## 2. Current Architecture

The existing system employs a three-tier public rendering pipeline where `Webkul/Theme` and `themes/base` sit between the Foundation Web kernel and the optional presentation layer:

```text
+-----------------------------------------------------------------------------------+
|                                   FOUNDATION                                      |
|                                                                                   |
|    +-------------------+           +-------------------+                          |
|    |    Webkul/Core    |           |    Webkul/User    |                          |
|    +---------+---------+           +-------------------+                          |
|              |                                                                    |
|              v                                                                    |
|    +-------------------+           +---------------------------------------+      |
|    |    Webkul/Web     | --------> |             Webkul/Theme              |      |
|    | (Component Kernel)|           |  - ThemeRegistry   - ThemeResolver    |      |
|    | - NavRegistry     |           |  - ThemeViewFinder - ThemeDefinition  |      |
|    | - SectionRegistry |           +-------------------+-------------------+      |
|    | - WebContext      |                               |                          |
|    +-------------------+                               v                          |
|                                            +-----------------------+              |
|                                            |      themes/base      |              |
|                                            |  - theme.json         |              |
|                                            |  - vite.config.js     |              |
|                                            |  - tailwind.config.js |              |
|                                            |  - assets/css/*.css   |              |
|                                            |  - views/overrides/*  |              |
|                                            +-----------+-----------+              |
+--------------------------------------------------------|--------------------------+
                                                         |
                                                         | (indirect styling &
                                                         |  master layout extension)
                                                         v
+-----------------------------------------------------------------------------------+
|                              OPTIONAL PRESENTATION                                |
|                                                                                   |
|                               +-------------------+                               |
|                               |  Webkul/Website   |                               |
|                               | - SiteDefinition  |                               |
|                               | - Page Sections   |                               |
|                               | - Header / Footer |                               |
|                               | - About Page      |                               |
|                               +---------+---------+                               |
|                                         |                                         |
|                                         v (public read contract)                  |
|                               +-------------------+                               |
|                               |   LostAndFound    |                               |
|                               |  (Public DTOs)    |                               |
|                               +-------------------+                               |
+-----------------------------------------------------------------------------------+
```

### Critical Flaws of the Current Architecture:
- `ThemeViewFinder` overrides Laravel's standard `view.finder` singleton, inspecting filesystem paths on every namespaced view lookup to check for `themes/base/views/overrides/{namespace}/{view}`.
- All Tailwind utility generation and Vite CSS compilation are owned by `themes/base`, forcing `themes/base/tailwind.config.js` to explicitly scan `packages/Webkul/Website`.
- `Webkul/Web` cannot build its own standalone interaction assets without delegating to `themes/base/vite.config.js`.
- `WebContext` stores `activeTheme`, coupling Foundation request state to an obsolete concept.

---

## 3. Target Architecture

The target architecture eliminates the Theme layer entirely. `Webkul/Web` acts as the permanent, project-neutral Foundation UI Kernel, while `Webkul/Website` acts as the self-contained, removable Presentation Package:

```text
                    FOUNDATION

                       Core
                        |
                       Web  (Reusable UI Kernel)
                        |
          +-------------+--------------+
          |             |              |
      Components    Registries     Interaction
          |             |              |
       Blade       Navigation       Vue 3 / JS
       Primitives  Sections         Runtime


                 OPTIONAL DOMAIN

              Student
                 ^
                 |
            LostAndFound (Public Read Contract DTOs)
                 |
                 | (read contract only)
                 v
              PRESENTATION

                 Website  (Application Presentation Package)
                    |
        +-----------+-----------+
        |           |           |
      Pages       Layouts      Assets
        |           |           |
      Home       Header       CSS (Tailwind v3)
      About      Footer       Vite Build
      Directory  Site Shell   Branding Assets
```

### Architectural Principles of the Target Architecture:
1. **Zero Theme Intermediary**: No `ThemeRegistry`, `ThemeResolver`, `ThemeViewFinder`, or `theme.json`.
2. **Direct Package Autonomy**: `Website` directly consumes generic Blade components from `<x-web::*>` and renders site-specific pages.
3. **Decoupled Asset Pipeline**: `Website` owns the visual CSS and site presentation build. `Web` owns the generic JavaScript interaction runtime and semantic default styling.
4. **Strict Isolation**: `Web` does not know `Website`, `Student`, `LostAndFound`, or university-specific branding.

---

## 4. Package Dependency Graph

The verified physical package dependency hierarchy is strictly unidirectional:

```text
[ Core ] <--------------------+--------------------+
   ^                          |                    |
   |                          |                    |
[ User ]                      |                    |
   ^                          |                    |
   |                          |                    |
[ Admin ]               [ Student ]                |
                              ^                    |
                              |                    |
                        [ LostAndFound ]           |
                              |                    |
                              | (Public Contract)  |
                              v                    |
[ Web ] <---------------- [ Website ] -------------+
```

### Dependency Audit:
- **`Webkul/Core`**: Base models, contracts, repositories, localization services, package composition. Depends on nothing in `Webkul/*`.
- **`Webkul/Web`**: UI Kernel, NavigationRegistry, SectionRegistry, WebContext, SEO service, Blade components, Vue interaction runtime. Depends strictly on `Webkul/Core`.
- **`Webkul/Website`**: Presentation package, SiteDefinition, site layouts, header/footer, homepage sections, about page, lost-and-found public views. Depends on `Webkul/Web`, `Webkul/Core`, and the public read contract of `Webkul/LostAndFound`.
- **`Webkul/LostAndFound`**: Domain workflows, claims, item management. Depends on `Webkul/Core` and optionally `Webkul/Student`. Zero dependency on `Webkul/Website` or `Webkul/Web`.
- **`Webkul/Student`**: Student auth, profiles. Zero dependency on `LostAndFound`, `Website`, or `Web`.

---

## 5. Web Responsibilities

`Webkul/Web` is the permanent, reusable Foundation UI Kernel. It encapsulates:

```text
Web Responsibilities:
├── WebContext (request locale, text direction LTR/RTL, canonical URL)
├── NavigationRegistry & Contracts (generic hierarchical navigation storage)
├── SectionRegistry & Contracts (extensible page section composition)
├── SeoService & Contracts (metadata management, OpenGraph, Twitter tags)
├── Blade Component Library (<x-web::*>)
│   ├── Static: button, badge, alert, card (index, header, content, footer), form.field, form.input
│   └── Interactive: accordion (index, item), modal, drawer, dropdown
├── Vue 3 Interaction Runtime (single createApp mount on #app, compiler-enabled)
│   ├── v-web-accordion (WebAccordion.js)
│   ├── v-web-modal (WebModal.js - focus trap, scroll lock, Escape, backdrop)
│   ├── v-web-drawer (WebDrawer.js - placement, focus trap, scroll lock)
│   └── v-web-dropdown (WebDropdown.js - keyboard navigation, menu positioning)
└── Generic Fallback Layout (web::layouts.master - accessible HTML skeleton)
```

### Web Project-Neutral Boundaries:
- `Web → Website`: **0 references**
- `Web → Student`: **0 references**
- `Web → LostAndFound`: **0 references**
- `Web → Specific Branding`: **0 references**

`Web` defines **WHAT** components do, **HOW** they behave, and guarantees standard accessibility (WCAG 2.1 AA, keyboard navigation, ARIA roles, focus restoration).

---

## 6. Website Responsibilities

`Webkul/Website` is the removable Presentation Package for the application. It encapsulates:

```text
Website Responsibilities:
├── SiteDefinition & Contract (name, short name, tagline, email, phone, hours, logos)
├── Site Master Layouts (<x-website::layout> or website::layouts.master)
│   ├── Header Presentation (branding, navigation links, locale switcher, mobile drawer)
│   └── Footer Presentation (site identity, navigation columns, copyright notice)
├── Public Application Pages
│   ├── Homepage composition via SectionRegistry (hero, features, announcements)
│   ├── About Us page (/about)
│   └── Lost & Found public directory (/lost-found, /lost-found/{reference})
├── Presentation Visual Styling
│   ├── Tailwind CSS configuration & site tokens
│   ├── Site stylesheet (website.css)
│   └── Presentation build configuration (Vite / PostCSS)
└── Visual Component Customization (overriding Web visual presentation while honoring Web behavior)
```

`Website` defines **HOW** the application looks, owning typography, color palettes, visual transitions, spacing, and site-specific branding.

---

## 7. Theme Package Forensic Audit (`packages/Webkul/Theme/`)

A physical line-by-line inspection of all 15 files in `packages/Webkul/Theme/` was performed:

| File / Symbol | Exact Purpose | Consumers | Runtime Critical? | Final Classification |
|---|---|---|---|---|
| `composer.json` | Package declaration for `webkul/theme`, registers `ThemeServiceProvider` | Composer | No (dead with Theme) | `DELETE_WITH_THEME` |
| `src/Config/themes.php` | Default config for `active`, `fallback`, and scan `paths` | `ThemeServiceProvider` | No | `DELETE_WITH_THEME` |
| `src/Contracts/ThemeRegistryContract.php` | Contract defining theme registration and inheritance chain resolution | `ThemeServiceProvider`, `ThemeResolver`, `ThemeRegistry`, tests | Yes (currently bound) | `DELETE_WITH_THEME` |
| `src/Contracts/ThemeResolverContract.php` | Contract for resolving active theme definition and inheritance chain | `ThemeServiceProvider`, `ResolveWebLocale`, `WebServiceProvider`, tests | Yes (currently bound) | `DELETE_WITH_THEME` |
| `src/Definitions/ThemeDefinition.php` | Readonly value object representing parsed `theme.json` | `ThemeRegistry`, `ThemeResolver`, `ThemeViewFinder`, `ThemeServiceProvider`, tests | Yes (currently used) | `DELETE_WITH_THEME` |
| `src/Exceptions/ThemeException.php` | Base exception for theme failures | Exception hierarchy | No | `DELETE_WITH_THEME` |
| `src/Exceptions/ThemeNotFoundException.php` | Thrown when an unregistered theme ID is requested | `ThemeRegistry`, tests | No | `DELETE_WITH_THEME` |
| `src/Exceptions/ThemeInheritanceCycleException.php` | Thrown when cyclic parent themes are detected | `ThemeRegistry`, tests | No | `DELETE_WITH_THEME` |
| `src/Exceptions/InvalidThemePathException.php` | Thrown when theme directory or manifest is missing/unreadable | `ThemeDefinition`, tests | No | `DELETE_WITH_THEME` |
| `src/Exceptions/MissingParentThemeException.php` | Thrown when a parent theme is missing from registry | `ThemeRegistry`, tests | No | `DELETE_WITH_THEME` |
| `src/Exceptions/InvalidThemeManifestException.php` | Thrown when `theme.json` is malformed or missing required keys | `ThemeDefinition`, tests | No | `DELETE_WITH_THEME` |
| `src/Providers/ThemeServiceProvider.php` | Registers `ThemeRegistry`, `ThemeResolver`, replaces `'view.finder'` with `ThemeViewFinder`, discovers themes | `bootstrap/providers.php`, Laravel container | Yes (boots in Foundation) | `DELETE_WITH_THEME` |
| `src/Registry/ThemeRegistry.php` | In-memory registry of `ThemeDefinition` instances with inheritance chain resolution | `ThemeServiceProvider`, tests | Yes | `DELETE_WITH_THEME` |
| `src/Resolution/ThemeResolver.php` | Resolves active `ThemeDefinition` via `config('themes.active')` | `ThemeServiceProvider`, `ResolveWebLocale`, `WebServiceProvider`, tests | Yes | `DELETE_WITH_THEME` |
| `src/View/ThemeViewFinder.php` | Extends `FileViewFinder`, intercepts `findNamespacedView` to search theme override directories | Laravel view factory (`view.finder`), `ResolveWebLocale`, tests | Yes (critical view resolution) | `DELETE_WITH_THEME` |

### Audit Conclusions for `Webkul/Theme`:
1. No domain package (`Student`, `LostAndFound`) consumes `Webkul/Theme`.
2. `Admin`, `User`, `Installer`, and `Core` do not consume `Webkul/Theme`.
3. `ThemeViewFinder` explicitly protects `admin`, `mail`, `notifications`, and `errors` namespaces.
4. Only `Webkul/Web` currently consumes `ThemeResolverContract` and `ThemeViewFinder`.
5. Every single symbol in `Webkul/Theme` is scheduled for removal once `Web` is decoupled.

---

## 8. Base Theme Forensic Audit (`themes/base/`)

A physical inspection of all 10 files in `themes/base/` was performed:

| File Path | Type | Responsibility / Purpose | Classification | Future Destination |
|---|---|---|---|---|
| `theme.json` | JSON | Theme manifest declaring `id: "base"`, `name: "Base"`, `views_path`, `assets_path` | `THEME_ONLY_INFRASTRUCTURE` | `DELETE_WITH_THEME` |
| `package.json` | JSON | NPM build script (`vite build --config vite.config.js`) | `BUILD_INFRASTRUCTURE` | `DELETE_WITH_THEME` |
| `vite.config.js` | JS | Vite config compiling `assets/css/theme.css` + `web-interactions.js` to `public/themes/base/build` | `BUILD_INFRASTRUCTURE` | `REBUILD_DIFFERENTLY` (in Website / Root) |
| `postcss.config.js` | JS | PostCSS plugin config loading Tailwind and Autoprefixer | `BUILD_INFRASTRUCTURE` | `MOVE_TO_WEBSITE` |
| `tailwind.config.js` | JS | Tailwind config defining content scan paths and typography/max-width extensions | `BUILD_INFRASTRUCTURE` | `MOVE_TO_WEBSITE` |
| `assets/css/theme.css` | CSS | 760 lines: design tokens, `.web-*` styling, responsive media queries, Tailwind base/components/utilities | `SITE_PRESENTATION` & `SITE_ASSET` | `MOVE_TO_WEBSITE` |
| `views/overrides/web/layouts/master.blade.php` | Blade | Overrides `web::layouts.master`, injects Vite CSS/JS assets, `data-theme="base"`, default nav | `SITE_PRESENTATION` | `MOVE_TO_WEBSITE` |
| `views/overrides/web/home/index.blade.php` | Blade | Overrides `web::home.index`, provides styled empty home container and section wrapper | `SITE_PRESENTATION` | `MOVE_TO_WEBSITE` |
| `views/overrides/web/components/button.blade.php` | Blade | Overrides `web::components.button`, wraps slot in `<span class="web-button__label">` | `GENERIC_WEB_COMPONENT_PRESENTATION` | `KEEP_OR_MOVE_TO_WEB` |
| `views/overrides/web/components/accordion/item.blade.php` | Blade | Overrides `web::components.accordion.item`, uses empty CSS icon span instead of `&rsaquo;` | `GENERIC_WEB_COMPONENT_PRESENTATION` | `KEEP_OR_MOVE_TO_WEB` |

---

## 9. Web Theme Dependencies

`Webkul/Web` currently contains direct references to `Webkul/Theme` in 2 production files:

### 1. `packages/Webkul/Web/src/Http/Middleware/ResolveWebLocale.php`:
```php
// Lines 10-11:
use Webkul\Theme\Contracts\ThemeResolverContract;
use Webkul\Theme\View\ThemeViewFinder;

// Line 20:
public function __construct(
    protected ContentLocaleService $contentLocaleService,
    protected LocaleRepository $localeRepository,
    protected ThemeResolverContract $themeResolver, // <--- Theme Dependency
) {}

// Lines 52-57:
$activeTheme = $this->themeResolver->resolveActiveTheme();
$viewFinder = view()->getFinder();

if ($viewFinder instanceof ThemeViewFinder) { // <--- Theme Dependency
    $viewFinder->setActiveThemeChain($this->themeResolver->resolveActiveInheritanceChain());
}

$webContext = new WebContext(
    locale: $effectiveLocale,
    direction: $direction,
    activeTheme: $activeTheme->id, // <--- Theme Dependency
    canonicalUrl: $request->url(),
);
```

### 2. `packages/Webkul/Web/src/Providers/WebServiceProvider.php`:
```php
// Line 7:
use Webkul\Theme\Contracts\ThemeResolverContract;

// Lines 36-42:
$this->app->scoped(WebContextContract::class, function ($app) {
    return new WebContext(
        locale: app()->getLocale(),
        direction: in_array(app()->getLocale(), ['ar', 'fa', 'he'], true) ? 'rtl' : 'ltr',
        activeTheme: $app->make(ThemeResolverContract::class)->resolveActiveTheme()->id, // <--- Theme Dependency
    );
});
```

### 3. `packages/Webkul/Web/src/Context/WebContext.php` & `WebContextContract.php`:
Both the implementation and interface declare:
```php
public function activeTheme(): string;
```

### Exact Current Dependency Flow:
```text
HTTP Request
     |
     v
ResolveWebLocale (Middleware)
     |---> ThemeResolverContract::resolveActiveTheme()
     |---> ThemeResolverContract::resolveActiveInheritanceChain()
     |---> ThemeViewFinder::setActiveThemeChain(...)
     |
     v
WebContext (constructed with activeTheme)
     |
     v
View Factory (ThemeViewFinder intercepts namespace resolution)
```

---

## 10. Website Theme Dependencies

Physical audit of `packages/Webkul/Website/` confirms:
- **Direct References to `Webkul\Theme`**: **0**
- **Direct References to `ThemeResolver` / `ThemeViewFinder`**: **0**
- **Direct References to `themes/base`**: **0**
- **Direct References to `theme.json`**: **0**

### The Hidden Indirect Dependency:
Despite zero direct code references, `Webkul/Website` is **100% dependent on `themes/base` at runtime**:
1. **Asset Injection**: Website has no layout that calls `@vite`. It relies on `themes/base/views/overrides/web/layouts/master.blade.php` to inject compiled CSS and JS into `<head>` and `<body>`.
2. **CSS Compilation**: All styles used by Website views (`.web-site-header`, `.web-navigation__link`, `.web-button`, etc.) are compiled inside `themes/base/assets/css/theme.css`.
3. **Tailwind Purging**: `themes/base/tailwind.config.js` explicitly includes `'../../packages/Webkul/Website/src/Resources/views/**/*.blade.php'`. If `themes/base` is removed without re-anchoring Tailwind, Website views will lose all CSS classes.

### What Prevents Website From Being Self-Contained Today?
1. Website does not own a master layout; it injects header and footer partials into Web's master layout via a View Composer hook (`View::composer('web::layouts.master', ...)`).
2. Website does not own a Vite or Tailwind build pipeline.
3. Website does not own the root route `/`; it only supplies section partials to `SectionRegistry`.

---

## 11. Root Application Audit

A complete search across all root directories (`app/`, `bootstrap/`, `config/`, `public/`, `routes/`, `tests/`, `package.json`) identified:

| Location | References | Category | Description |
|---|---|---|---|
| `bootstrap/providers.php` | 1 | `PRODUCTION_RUNTIME` | Line 10: `Webkul\Theme\Providers\ThemeServiceProvider::class` |
| `package.json` | 2 | `BUILD_TIME` | Lines 5-6: `"dev": "vite --config themes/base/vite.config.js"`, `"build": "vite build --config themes/base/vite.config.js"` |
| `tests/Composition/FoundationOnlyApplicationTest.php` | 8 | `TEST_ONLY` | Tests that `ThemeServiceProvider`, `ThemeRegistryContract`, `ThemeResolverContract`, and `data-theme="base"` boot in Foundation |
| `tests/Feature/Web/WebComponentKernelTest.php` | 3 | `TEST_ONLY` | Unused imports of Theme contracts in test file |
| `tests/Feature/Web/WebRootHomepageTest.php` | 2 | `TEST_ONLY` | Asserts `data-theme="base"` in English and Arabic root HTML |
| `tests/Feature/Web/PublicWebVueKernelTest.php` | 3 | `TEST_ONLY` | Verifies `public/themes/base/build/manifest.json` and compiled assets |
| `tests/Feature/Web/WebNavigationLocalizationTest.php` | 1 | `TEST_ONLY` | Asserts `data-theme="base"` in Arabic RTL response |
| `tests/Feature/Theme/*` (6 files) | 72 | `TEST_ONLY` | Comprehensive test suite verifying Theme engine features (registry, resolver, view resolution, manifests, cycle detection) |

**Total Root References**: **92 references** (1 runtime, 2 build-time, 89 test-only).  
`config/themes.php` does not exist in root `config/` (merged from package).

---

## 12. Current Blade Component Inventory

The existing Web component library located in `packages/Webkul/Web/src/Resources/views/components/` comprises **14 Blade template files** spanning **9 component families**:

| Component Name | Blade Source File | Family / Primitive | Props | Slots | Interactive? | Classification |
|---|---|---|---|---|---|---|
| `<x-web::button>` | `button.blade.php` | Button | `type`, `variant`, `size`, `disabled`, `href`, `target` | Default (`$slot`) | No (static HTML / link) | `STATIC_BLADE` |
| `<x-web::badge>` | `badge.blade.php` | Badge | `variant`, `size` | Default (`$slot`) | No (static `<span>`) | `STATIC_BLADE` |
| `<x-web::alert>` | `alert.blade.php` | Alert | `variant`, `title`, `dismissible` | Default (`$slot`) | Progressive (JS dismiss) | `FEEDBACK` |
| `<x-web::card>` | `card/index.blade.php` | Card Container | `as`, `header`, `footer` | `header`, `footer`, default | No (semantic wrapper) | `STRUCTURAL` |
| `<x-web::card.header>` | `card/header.blade.php` | Card Header | None | Default (`$slot`) | No (`<header>`) | `STRUCTURAL` |
| `<x-web::card.content>` | `card/content.blade.php` | Card Content | None | Default (`$slot`) | No (`<div>`) | `STRUCTURAL` |
| `<x-web::card.footer>` | `card/footer.blade.php` | Card Footer | None | Default (`$slot`) | No (`<footer>`) | `STRUCTURAL` |
| `<x-web::form.field>` | `form/field.blade.php` | Form Control Group | `name`, `label`, `id`, `required`, `help`, `error` | Default (`$slot`) | No (labels, error, help) | `FORM` |
| `<x-web::form.input>` | `form/input.blade.php` | Form Input | `name`, `id`, `type`, `value`, `disabled`, `readonly`, `required`, `placeholder`, `autocomplete`, `invalid`, `describedBy` | None | No (`<input>`) | `FORM` |
| `<x-web::accordion>` | `accordion/index.blade.php` | Accordion Root | `id`, `flush`, `alwaysOpen` | Default (`$slot`) | Yes (`v-web-accordion`) | `INTERACTIVE_BLADE_VUE` |
| `<x-web::accordion.item>`| `accordion/item.blade.php` | Accordion Panel | `id`, `title`, `expanded` | `header`, default (`$slot`)| Yes (ARIA controls) | `INTERACTIVE_BLADE_VUE` |
| `<x-web::modal>` | `modal.blade.php` | Modal Dialog | `id`, `title`, `size`, `open` | `trigger`, `header`, `footer`, default | Yes (`v-web-modal`) | `INTERACTIVE_BLADE_VUE` |
| `<x-web::drawer>` | `drawer.blade.php` | Slideout Drawer | `id`, `title`, `placement`, `open`| `trigger`, `header`, `footer`, default | Yes (`v-web-drawer`) | `INTERACTIVE_BLADE_VUE` |
| `<x-web::dropdown>` | `dropdown.blade.php` | Dropdown Menu | `id`, `align`, `width` | `trigger`, default (`$slot`) | Yes (`v-web-dropdown`) | `INTERACTIVE_BLADE_VUE` |

---

## 13. Static Component Inventory

There are **9 static component files** (5 families):
1. **Button** (`button.blade.php`): Renders `<button>` or `<a role="button">` with normalized variants (`primary`, `secondary`, `outline`, `ghost`, `danger`) and sizes (`sm`, `md`, `lg`). Accessible `aria-disabled="true"` and `tabindex="-1"` on disabled links.
2. **Badge** (`badge.blade.php`): Renders semantic inline badge (`primary`, `secondary`, `neutral`, `success`, `warning`, `danger`) with sizes (`sm`, `md`).
3. **Alert** (`alert.blade.php`): Renders accessible notification banners (`info`, `success`, `warning`, `danger`). Urgent alerts receive `role="alert"`; informational receive `role="status"`. Optional dismiss button with `aria-label="Close"`.
4. **Card Primitives** (`card/index.blade.php`, `header.blade.php`, `content.blade.php`, `footer.blade.php`): Structural container allowing polymorphic root element (`div`, `article`, `section`), with optional slotted or subcomponent header and footer.
5. **Form Primitives** (`form/field.blade.php`, `form/input.blade.php`): Accessible form group binding `<label for="...">`, `<input id="...">`, `aria-describedby` pointing to error and help text, with `aria-invalid="true"` and `role="alert"` on errors.

---

## 14. Interactive Component Inventory

There are **5 interactive component files** (4 families), all powered by compiler-enabled Vue 3 components mounted over server-rendered Blade:

1. **Accordion** (`accordion/index.blade.php` & `accordion/item.blade.php`):
   - Renders `<v-web-accordion>` root and `<button class="web-accordion__trigger">`.
   - Behavior: ARIA `aria-expanded`, `aria-controls`, `role="region"`, `aria-labelledby`. Supports `alwaysOpen` mode. Keyboard support: Up/Down arrow navigation, Home/End, Enter/Space toggle, Escape collapse.
2. **Modal** (`modal.blade.php`):
   - Renders `<v-web-modal>` wrapping dialog surface and backdrop.
   - Behavior: ARIA `role="dialog"`, `aria-modal="true"`, `aria-labelledby`. Focus trap (Tab / Shift+Tab cycling), focus restoration to opener, background scroll lock (`overflow-hidden` on `<html>`), Escape dismiss, backdrop click dismiss.
3. **Drawer** (`drawer.blade.php`):
   - Renders `<v-web-drawer>` with placement (`start`, `end`, `top`, `bottom`).
   - Behavior: ARIA `role="dialog"`, `aria-modal="true"`, focus trap, focus restoration, scroll lock, Escape dismiss, backdrop dismiss. Logical RTL/LTR placement mapping.
4. **Dropdown** (`dropdown.blade.php`):
   - Renders `<v-web-dropdown>` with trigger slot and popover menu.
   - Behavior: ARIA `aria-haspopup="true"`, `aria-expanded`, ArrowDown/ArrowUp keyboard menu navigation, Home/End, Escape to close and restore focus to trigger, outside click detection.

---

## 15. Vue Runtime Audit

A physical inspection of the Vue runtime located in `packages/Webkul/Web/src/Resources/assets/js/` was performed:

```text
packages/Webkul/Web/src/Resources/assets/js/
├── web-interactions.js       <-- Entrypoint (delegated handlers + mounts Vue)
└── vue/
    ├── app.js               <-- Single createApp({ name: 'CampusHubPublicWeb' })
    ├── components.js        <-- Component registration dictionary
    └── components/
        ├── WebAccordion.js   <-- Vue component for accordion
        ├── WebModal.js       <-- Vue component for modal dialog
        ├── WebDrawer.js      <-- Vue component for drawer
        └── WebDropdown.js    <-- Vue component for dropdown
```

### Vue Runtime Parameters:
- **Vue Version**: `^3.5.43` (declared in root `package.json`).
- **Import Strategy**: `import { createApp } from 'vue/dist/vue.esm-bundler.js'` (Compiler-enabled build, allowing inline Blade templates and slots).
- **`createApp` Count**: Exactly **1** (`createApp({ name: 'CampusHubPublicWeb' })`).
- **Mount Target**: `#app` (`document.getElementById('app')`). Guarded by `root.dataset.webVueMounted = 'true'` to prevent duplicate mounts.
- **Global Component Registrations**:
  - `v-web-accordion` → `WebAccordion`
  - `v-web-modal` → `WebModal`
  - `v-web-drawer` → `WebDrawer`
  - `v-web-dropdown` → `WebDropdown`
- **Legacy Interactions (Non-Vue)** in `web-interactions.js`:
  1. `document.addEventListener('click')` for `[data-web-alert-dismiss]` (removes alert element).
  2. `document.addEventListener('click')` for `[data-web-nav-toggle]` and `[data-web-nav-panel]` (mobile navigation drawer).
  3. `window.matchMedia('(min-width: 48rem)')` listener (auto-closes mobile navigation on resize to desktop).

### Blade-to-Vue Component Relationship:
```text
Blade Public API                  Vue Internal Component            State / ARIA Lifecycle
----------------                  ----------------------            ----------------------
<x-web::modal>          ---->     <v-web-modal>           ---->     isOpen, focusTrap, scrollLock
<x-web::drawer>         ---->     <v-web-drawer>          ---->     isOpen, placement, focusTrap
<x-web::dropdown>       ---->     <v-web-dropdown>        ---->     isOpen, keyboardIndex, outsideClick
<x-web::accordion>      ---->     <v-web-accordion>       ---->     expandedPanelIds, keyboardNav
```

---

## 16. Component Behavior Ownership Contract

To ensure that presentation packages (`Website`) never break core functionality, components are divided into **Immutable Behavior** (owned exclusively by `Web`) and **Overridable Presentation** (controlled by `Website`):

### Contract Matrix:

| Component | Web-Owned Immutable Behavior (CANNOT BE OVERRIDDEN) | Website-Controlled Presentation (CAN BE CUSTOMIZED) |
|---|---|---|
| **Modal** | Open/close state machine, focus trap, focus restoration, `aria-modal="true"`, `role="dialog"`, Escape listener, backdrop click dismiss, scroll lock | Backdrop blur intensity, dialog border radius, background color, shadow depth, padding, close icon SVG, enter/leave animation classes |
| **Drawer** | Slideout state, logical placement (`start`/`end`), focus trap, focus restoration, `role="dialog"`, Escape dismiss, backdrop dismiss, scroll lock | Drawer width, shadow, background color, header border, typography, close icon styling |
| **Dropdown** | Popover open/close state, trigger ARIA attributes (`aria-expanded`), ArrowUp/ArrowDown item navigation, Escape key handler, outside-click listener | Menu border radius, elevation shadow, alignment offset, item hover highlight, divider styling |
| **Accordion** | Single vs multi-expand logic (`alwaysOpen`), panel `hidden` state toggle, ARIA associations (`aria-controls`, `aria-labelledby`, `aria-expanded`), Arrow navigation | Border styles, trigger padding, chevron indicator icon/rotation, font weight, background color |
| **Button** | `<button>` vs `<a>` tag resolution, disabled state handling (`aria-disabled`, `tabindex="-1"`), form submit propagation | Color schemes (primary, secondary, etc.), corner radius, padding, elevation, hover/active scale |
| **Alert** | `role="alert"` vs `role="status"` based on urgency, dismiss trigger event, DOM removal | Border accent thickness, alert surface tint, close button styling, typography |
| **Form Field** | Collision-safe ID generation, `<label for>`, `aria-describedby` linking errors & help, `aria-invalid` state | Spacing, label font size, error color, asterisk color, field layout |

---

## 17. Bagisto Shop Component Reference Audit

Using the Bagisto 2.4 reference codebase located at `bagisto-2.4/packages/Webkul/Shop/src/Resources/views/components/`, an audit of Bagisto's component system was performed:

### Bagisto Component Catalog Audit:
Bagisto Shop contains **22 component families**:
1. `accordion` (`<x-shop::accordion>`)
2. `breadcrumbs` (`<x-shop::breadcrumbs>`)
3. `button` (`<x-shop::button>`)
4. `carousel` (`<x-shop::carousel>`)
5. `categories` (`<x-shop::categories.*>`)
6. `datagrid` (`<x-shop::datagrid.*>`)
7. `drawer` (`<x-shop::drawer>`)
8. `dropdown` (`<x-shop::dropdown>`)
9. `flash-group` (`<x-shop::flash-group>`)
10. `flat-picker` (`<x-shop::flat-picker.date>`)
11. `form` (`<x-shop::form.control-group.*>`)
12. `image-zoomer` (`<x-shop::image-zoomer>`)
13. `layouts` (`<x-shop::layouts.*>`)
14. `media` (`<x-shop::media.images.lazy>`)
15. `modal` (`<x-shop::modal>`, `<x-shop::modal.confirm>`)
16. `products` (`<x-shop::products.*>`)
17. `quantity-changer` (`<x-shop::quantity-changer>`)
18. `range-slider` (`<x-shop::range-slider>`)
19. `shimmer` (`<x-shop::shimmer.*>`)
20. `table` (`<x-shop::table.*>`)
21. `tabs` (`<x-shop::tabs.*>`)
22. `tinymce` (`<x-shop::tinymce>`)

---

## 18. Generic Bagisto Candidates

Of the 22 Bagisto component families, **15 families represent generic web patterns** suitable for inclusion or inspiration in `Webkul/Web`:

| Bagisto Component | Reusable Pattern | CampusFind Current Status | Recommendation for Web |
|---|---|---|---|
| `button` | Polymorphic button/link | Implemented (`<x-web::button>`) | Keep existing Web implementation |
| `modal` | Modal dialog + confirmation modal | Implemented (`<x-web::modal>`) | Retain; add `<x-web::modal.confirm>` in future |
| `drawer` | Slideout panel | Implemented (`<x-web::drawer>`) | Retain existing Web implementation |
| `dropdown` | Action popover menu | Implemented (`<x-web::dropdown>`) | Retain existing Web implementation |
| `accordion` | Collapsible FAQ / panel | Implemented (`<x-web::accordion>`) | Retain existing Web implementation |
| `form` | Field, control, label, error | Implemented (`<x-web::form.field>`) | Expand with select, textarea, checkbox, radio |
| `tabs` | Tabbed navigation panel | Not in Web | High value generic candidate (P1) |
| `breadcrumbs` | Navigational trail | Not in Web | High value generic candidate (P1) |
| `table` | Semantic data table primitives | Not in Web | High value generic candidate (P1) |
| `flash-group` | Toast/flash notifications | Partially in Web (`<x-web::alert>`) | Add floating toast container (P1) |
| `shimmer` | Skeleton loading placeholder | Not in Web | Useful UI feedback primitive (P2) |
| `media` | Lazy loading responsive image | Not in Web | Generic asset helper (P2) |
| `carousel` | Visual item slider | Not in Web | Useful UI primitive (P2) |
| `range-slider` | Numeric range input | Not in Web | Optional form primitive (P2) |
| `flat-picker` | Date / time picker wrapper | Not in Web | Optional form primitive (P2) |

---

## 19. Rejected Commerce Components

The following **7 Bagisto component families are explicitly rejected** from `Webkul/Web` because they represent domain-specific e-commerce concepts:

1. **`products`** (`products/card.blade.php`, `products/carousel.blade.php`, `products/ratings.blade.php`): E-commerce catalog entity. Belong strictly in commerce packages, not generic Web.
2. **`categories`** (`categories/carousel.blade.php`, `categories/filters.blade.php`, `categories/view.blade.php`): E-commerce taxonomy.
3. **`quantity-changer`** (`quantity-changer/index.blade.php`): Shopping cart quantity modifier.
4. **`checkout`** (`checkout/cart`, `checkout/onepage`, `checkout/shipping-method`, `checkout/payment-method`): Commerce purchasing funnel.
5. **`layouts/account`** (`customers/account/orders`, `customers/account/wishlist`, `customers/account/rma`): Customer account management for retail shoppers.
6. **`tinymce`** (`tinymce/index.blade.php`): Heavy third-party WYSIWYG editor. Belongs in Admin or CMS packages.
7. **`image-zoomer`** (`image-zoomer/index.blade.php`): Specialized retail product image magnification.

---

## 20. Proposed Web Component Catalog

Combining current Web components, reusable Bagisto patterns, and CampusFind reusable seed requirements, the target catalog is categorized by priority:

| Component Name | Category | Status | Static / Interactive | Why Generic? | Behavior Owner | Presentation Owner | Priority |
|---|---|---|---|---|---|---|---|
| `<x-web::button>` | Actions | Existing | Static | Standard call to action | Web | Website / Web Default | **P0** |
| `<x-web::card>` | Structural | Existing | Static | Semantic content grouping | Web | Website / Web Default | **P0** |
| `<x-web::badge>` | Feedback | Existing | Static | Status and category tags | Web | Website / Web Default | **P0** |
| `<x-web::alert>` | Feedback | Existing | Progressive | In-page notifications | Web | Website / Web Default | **P0** |
| `<x-web::form.field>` | Forms | Existing | Static | Form input wrapper with ARIA | Web | Website / Web Default | **P0** |
| `<x-web::form.input>` | Forms | Existing | Static | Text/email/password input | Web | Website / Web Default | **P0** |
| `<x-web::modal>` | Overlay | Existing | Interactive (Vue) | Accessible dialog | Web | Website / Web Default | **P0** |
| `<x-web::drawer>` | Overlay | Existing | Interactive (Vue) | Accessible slideout drawer | Web | Website / Web Default | **P0** |
| `<x-web::dropdown>` | Overlay | Existing | Interactive (Vue) | Popover menu | Web | Website / Web Default | **P0** |
| `<x-web::accordion>` | Data Display | Existing | Interactive (Vue) | Collapsible disclosure panels | Web | Website / Web Default | **P0** |
| `<x-web::form.textarea>` | Forms | Proposed | Static | Multiline text control | Web | Website / Web Default | **P1** |
| `<x-web::form.select>` | Forms | Proposed | Static | Native select dropdown | Web | Website / Web Default | **P1** |
| `<x-web::form.checkbox>`| Forms | Proposed | Static | Boolean / array choice | Web | Website / Web Default | **P1** |
| `<x-web::form.radio>` | Forms | Proposed | Static | Single option selection | Web | Website / Web Default | **P1** |
| `<x-web::tabs>` | Navigation | Proposed | Interactive (Vue) | Tabbed content panels | Web | Website / Web Default | **P1** |
| `<x-web::breadcrumbs>` | Navigation | Proposed | Static | Hierarchical location trail | Web | Website / Web Default | **P1** |
| `<x-web::table>` | Data Display | Proposed | Static | Accessible data presentation | Web | Website / Web Default | **P1** |
| `<x-web::toast>` | Feedback | Proposed | Interactive (Vue) | Floating transient notification| Web | Website / Web Default | **P1** |
| `<x-web::modal.confirm>`| Overlay | Proposed | Interactive (Vue) | Standardized confirm prompt | Web | Website / Web Default | **P1** |
| `<x-web::shimmer>` | Feedback | Proposed | Static | Skeleton loading states | Web | Website / Web Default | **P2** |
| `<x-web::media>` | Media | Proposed | Static | Responsive lazy image | Web | Website / Web Default | **P2** |
| `<x-web::carousel>` | Media | Proposed | Interactive (Vue) | Responsive swipe slider | Web | Website / Web Default | **P2** |
| `<x-web::product.*>` | Commerce | Rejected | — | Commerce domain concept | — | — | **REJECT** |
| `<x-web::cart.*>` | Commerce | Rejected | — | Commerce domain concept | — | — | **REJECT** |

*(Note: In accordance with Phase 15 Step 01 rules, zero components are implemented in this step).*

---

## 21. Presentation Override Requirements

When `Website` needs to customize the visual appearance of a Web component, the consumer API must remain stable:

```blade
{{-- Callers always write the canonical Web contract: --}}
<x-web::button variant="primary">Submit</x-web::button>
<x-web::modal id="claim-modal" title="Submit Claim">...</x-web::modal>
```

Callers must **never** be forced to alter template tags to `<x-website::button>` merely because the visual theme changes.

The architecture must satisfy:
1. **Deterministic Resolution**: If a presentation override exists, render it; otherwise render Web's default.
2. **Behavioral Integrity**: Presentation overrides must never break or duplicate Vue lifecycle, ARIA attributes, or keyboard listeners.
3. **No Theme Engine Re-creation**: No `PresentationRegistry`, no multi-tenant presentation switching, no `APP_PRESENTATION` env variable, no dynamic filesystem scanners. There is exactly **one** composed Presentation Package (`Website`).

---

## 22. Override Model A Analysis — Full Blade Override

```text
Mechanism: Website registers a view namespace or overrides path that completely replaces
           packages/Webkul/Web/src/Resources/views/components/{component}.blade.php.
```

- **Simplicity**: High initially (standard Laravel view path prepending).
- **Laravel-Native**: Yes (`View::prependNamespace('web', ...)`).
- **Blade Compatibility**: High.
- **Vue Compatibility**: Low/Dangerous. If the overriding Blade file accidentally removes `<v-web-modal>`, modifies `data-web-modal-dialog`, or strips `aria-modal="true"`, Vue crashes or accessibility breaks silently.
- **Upgrade Safety**: Extremely Poor. Any upstream fix in `Web` (e.g., security fix, ARIA compliance fix, focus trap tweak) is bypassed by Website's static copy.
- **Risk of Drift**: Extreme.
- **Verdict**: **REJECTED**.

---

## 23. Override Model B Analysis — Logic Wrapper + Overridable Visual Partial

```text
Mechanism: <x-web::modal> remains the immutable behavior shell.
           It renders the outer <v-web-modal> element, generates collision-safe IDs,
           and binds ARIA attributes.
           Inside the shell, it checks for an overridable visual partial:
           if (view()->exists('website::components.modal.surface')) {
               @include('website::components.modal.surface', $context)
           } else {
               @include('web::components.modal.default-surface', $context)
           }
           Or for static components: CSS design tokens and utility overrides via Website's stylesheet.
```

- **Simplicity**: Very high.
- **Laravel-Native**: Standard Blade component with nested slots and partial inclusion.
- **Blade Compatibility**: 100%.
- **Vue Compatibility**: 100%. The Vue component wrapper and data attributes are physically managed by Web's outer template and can never be omitted by presentation authors.
- **Accessibility Safety**: Absolute. ARIA roles, tabindex, and focus boundaries remain locked inside Web.
- **Upgrade Safety**: Excellent. Web updates behavior transparently.
- **Risk of Drift**: Minimal.
- **Verdict**: **RECOMMENDED**.

---

## 24. Override Model C Analysis — Web Contract + Presentation Renderer Service

```text
Mechanism: Every Blade component delegates to a PHP service (ComponentPresentationRendererContract)
           injected via the service container.
```

- **Simplicity**: Low. Over-engineered indirection.
- **Laravel-Native**: Deviates from standard Blade component conventions.
- **Blade Compatibility**: Moderate (requires PHP class components with injected renderers).
- **Risk of Complexity**: Unnecessary overhead for server-rendered HTML.
- **Verdict**: **REJECTED**.

---

## 25. Recommended Override Model

**MODEL B (Logic Wrapper + Overridable Visual Partial)** is the chosen architectural contract:

1. **For Static Primitives** (`Button`, `Card`, `Badge`, `Alert`, `Input`):
   - Visual customization is achieved via **Tailwind utility classes and CSS Custom Properties** in `Website`'s stylesheet (`--color-primary`, `--radius-md`, etc.).
   - If structural visual changes are needed, Web components provide named slots (`<x-slot:header>`, `<x-slot:icon>`) or optional presentation partial delegates.
2. **For Interactive Components** (`Modal`, `Drawer`, `Accordion`, `Dropdown`):
   - `Web` owns the outer Blade component and Vue element (`<v-web-modal>`, `<v-web-drawer>`), ensuring 100% adherence to ARIA, focus traps, and keyboard event handling.
   - The visual chrome (borders, backdrops, close buttons, padding) is rendered via overridable slots or presentation partials that `Website` can customize without touching Vue logic.

---

## 26. Default Web Presentation Requirement

`Webkul/Web` must remain fully operational when `Webkul/Website` is absent.

Therefore:
1. Every `<x-web::*>` component must contain a **safe, functional default presentation**.
2. Default presentation relies on standard semantic HTML5 elements and neutral utility classes.
3. If no presentation stylesheet is loaded, components still render structurally intact, accessible, and usable.
4. Foundation-only applications must be able to boot, render, and pass all tests without `Webkul/Website`.

---

## 27. Layout Ownership Decision

### Current Situation:
- `Web` owns a minimal `web::layouts.master`.
- `themes/base` overrides it with `themes/base/views/overrides/web/layouts/master.blade.php`.
- `Website` hooks into `web::layouts.master` via View Composers to inject its header and footer.

### Target Architecture Decision:
1. **`Webkul/Web` owns `<x-web::layouts.master>`**:
   - Provides an accessible document shell (`<!DOCTYPE html>`, `<html>`, `<head>`, `<body>`, `#app` Vue mount point, skip-to-content link, `@yield('content')`).
   - Includes fallback header and footer slots for Foundation-only mode.
2. **`Webkul/Website` owns `<x-website::layout>` (or `website::layouts.master`)**:
   - Extends or wraps `web::layouts.master`.
   - Injects CampusFind branding, SiteDefinition metadata, primary site header, mobile navigation drawer, site footer, and social links.
   - Loads Website's compiled CSS and JS assets via `@vite`.
3. **Outcome**:
   - `Website` pages (`/`, `/about`, `/lost-found`) extend `website::layouts.master`.
   - Foundation-only fallback pages extend `web::layouts.master`.
   - No view composer hijacking is required.

---

## 28. NavigationRegistry Ownership & Decoupling

`NavigationRegistry` is located at `packages/Webkul/Web/src/Navigation/NavigationRegistry.php`.

### Findings:
- Bound as a singleton via `NavigationRegistryContract`.
- Supports locations: `header`, `footer`, `mobile`, `secondary`.
- Sanitizes URLs against unsafe executable schemes (`javascript:`, `data:`, `vbscript:`).
- `Website` registers items via `WebsiteServiceProvider::registerNavigationItems()`:
  - `website_home` (`/`)
  - `website_about` (`/about`)
  - `website_footer_home` (`/`)
  - `website_footer_about` (`/about`)
- When `LostAndFound` is present, `WebsiteLostAndFoundServiceProvider` registers:
  - `website_lost_found` (`/lost-found`)
  - `website_footer_lost_found` (`/lost-found`)
- **Boundary Verification**: `Webkul/Web` contains **0 references** to `Website` navigation IDs. `Website` contributes cleanly without Web knowing of its existence.
- **Verdict**: **RETAIN IN WEBKUL/WEB**.

---

## 29. SectionRegistry Ownership & Decoupling

`SectionRegistry` is located at `packages/Webkul/Web/src/Sections/SectionRegistry.php`.

### Findings:
- Bound as a singleton via `SectionRegistryContract`.
- Stores sections grouped by page (`home`, etc.), sorted by order.
- `HomeController` in `Web` queries `$this->sectionRegistry->getSections('home')` and passes them to `web::home.index`.
- `Website` registers homepage sections via `WebsiteServiceProvider::registerHomepageSections()`:
  - `website_hero` (order 10)
  - `website_features` (order 20)
  - `website_announcements` (order 30)
- `WebsiteLostAndFoundServiceProvider` registers:
  - `website_lost_found` (order 25)
- When `Website` is absent, `SectionRegistry` returns 0 sections, and `HomeController` renders the default empty home template.
- **Verdict**: **RETAIN IN WEBKUL/WEB**.

---

## 30. SiteDefinition Ownership

`SiteDefinition` is located at `packages/Webkul/Website/src/SiteDefinition/SiteDefinition.php`.

### Findings:
- Readonly value object representing organization/site identity: name, short name, tagline, email, phone, office hours, logo URL, favicon URL, and SEO defaults.
- Resolved via `SiteDefinitionResolver` implementing `SiteDefinitionContract`.
- Bound in `WebsiteServiceProvider`.
- Consumed by `website::partials.header`, `website::partials.footer`, `website::pages.about`, and `SeoService::setDefaultsResolver()`.
- **Verdict**: **RETAIN IN WEBKUL/WEBSITE**. SiteDefinition represents site-specific identity and belongs strictly in the Presentation Package.

---

## 31. LostAndFound Presentation Boundary

`packages/Webkul/Website/src/Integrations/LostAndFound/` encapsulates all public Lost & Found views.

### Findings:
- `WebsiteLostAndFoundServiceProvider` conditionally boots only when `lost_and_found` is present in `OptionalPackageComposition`.
- Depends strictly on the public read contract `Webkul\LostAndFound\Contracts\PublicLostAndFoundReadContract`.
- Zero database queries are executed by Website controllers; data is consumed via readonly DTOs (`PublicFoundItemDto`).
- `LostAndFound` contains **0 references** to `Website`.
- `Web` contains **0 references** to `LostAndFound`.
- **Verdict**: **PRISTINE CONTRACT**. Theme removal does not impact this integration.

---

## 32. Current Frontend Build Flow

The current build pipeline is routed through `themes/base`:

```text
SOURCE
├── themes/base/assets/css/theme.css  (CSS tokens, Tailwind directives)
└── packages/Webkul/Web/src/Resources/assets/js/web-interactions.js  (Vue runtime + JS)
       |
       v
BUILD RUNNER
├── Root package.json ("build": "vite build --config themes/base/vite.config.js")
├── themes/base/vite.config.js
├── themes/base/postcss.config.js
└── themes/base/tailwind.config.js (scans themes/base, Web, and Website)
       |
       v
OUTPUT
└── public/themes/base/build/
    ├── manifest.json
    ├── assets/theme-*.css
    └── assets/web-interactions-*.js
       |
       v
LAYOUT INJECTION
└── themes/base/views/overrides/web/layouts/master.blade.php
    ├── @vite('assets/css/theme.css', 'themes/base/build')
    └── @vite('../../packages/Webkul/Web/src/Resources/assets/js/web-interactions.js', 'themes/base/build')
       |
       v
BROWSER
```

---

## 33. Target Frontend Build Flow

In the target architecture, the build runner is anchored at the root/website level, outputting to standard Laravel `public/build` (or `public/vendor/website/build`):

```text
SOURCE
├── packages/Webkul/Website/src/Resources/assets/css/website.css  (Site presentation CSS)
└── packages/Webkul/Web/src/Resources/assets/js/web-interactions.js  (Web interaction JS)
       |
       v
BUILD RUNNER
├── Root package.json ("build": "vite build")
├── Root / Website vite.config.js
├── Root / Website postcss.config.js
└── Root / Website tailwind.config.js (scans Web components + Website views)
       |
       v
OUTPUT
└── public/build/ (standard Laravel Vite manifest)
    ├── manifest.json
    ├── assets/website-*.css
    └── assets/web-interactions-*.js
       |
       v
LAYOUT INJECTION
└── packages/Webkul/Website/src/Resources/views/layouts/master.blade.php
    └── @vite(['packages/Webkul/Website/src/Resources/assets/css/website.css', 'packages/Webkul/Web/src/Resources/assets/js/web-interactions.js'])
       |
       v
BROWSER
```

---

## 34. Foundation-Only Build Strategy

### The Problem:
If `Website` owns the primary Vite/Tailwind build, what happens when `Website` is physically absent?

### Evaluation of Options:
1. **Option A: Foundation Web owns a duplicate fallback build**:
   - *Downside*: Duplicates Node dependencies, double `package.json`, redundant Vite configurations.
2. **Option B: Foundation-only mode has no public presentation build**:
   - *Downside*: Unstyled HTML in development if testing Foundation visually.
3. **Option C: Root Vite configuration with deterministic fallback (RECOMMENDED)**:
   - Root `vite.config.js` acts as the orchestrator.
   - It checks whether Website source assets exist.
   - If `Website` is present: compiles `website.css` and `web-interactions.js` to `public/build`.
   - If `Website` is absent: compiles a minimal `packages/Webkul/Web/src/Resources/assets/css/web-fallback.css` and `web-interactions.js` to `public/build`.
   - In production releases, pre-compiled assets remain in `public/build` so headless CI or Foundation-only PHP tests never require `npm install` or node runtime to pass HTTP assertion tests!

---

## 35. Tailwind Content Ownership

### Current Flaw:
`themes/base/tailwind.config.js` hardcodes:
```js
content: {
    files: [
        './views/**/*.blade.php',
        '../../packages/Webkul/Web/src/Resources/views/**/*.blade.php',
        '../../packages/Webkul/Web/src/Resources/assets/js/**/*.js',
        '../../packages/Webkul/Theme/src/Resources/views/**/*.blade.php',
        '../../packages/Webkul/Website/src/Resources/views/**/*.blade.php',
    ],
}
```
Foundation's theme configuration explicitly knows about optional package `Website`.

### Target Content Contract:
The Tailwind configuration must belong to the **Presentation build owner** (`Website` or root build):
- Scans `packages/Webkul/Web/src/Resources/views/**/*.blade.php` (for generic component utility classes).
- Scans `packages/Webkul/Web/src/Resources/assets/js/**/*.js` (for dynamically injected classes).
- Scans `packages/Webkul/Website/src/Resources/views/**/*.blade.php` (for site pages).
- Removes the non-existent `Webkul/Theme` path.

---

## 36. Vite Ownership

### Decision:
Vite build configuration moves out of `themes/base/vite.config.js`.
It is anchored in the root `vite.config.js` (or `packages/Webkul/Website/vite.config.js`), utilizing standard Laravel Vite conventions (`publicDirectory: 'public'`, `buildDirectory: 'build'`).
The custom hotfile `public/base-theme-vite.hot` is replaced with standard `public/hot`.

---

## 37. Route Ownership: Who Owns `/`?

### Evaluation:
- **Model A: Web owns `/` and composes Website sections via SectionRegistry (CURRENT & RECOMMENDED)**:
  `Web` registers `Route::get('/', [HomeController::class, 'index'])->name('web.home')`.
  `HomeController` queries `SectionRegistry::getSections('home')`.
  When `Website` is present, it registers its hero, features, and announcements sections.
  When `Website` is removed, `HomeController` renders the default empty home view.
  - *Advantages*: Zero route collisions, zero conditional route overrides, 100% physically removable, zero reflection.
- **Model B: Website conditionally overrides `/`**:
  - *Disadvantages*: Introduces route overriding complexity, ordering dependencies, and potential route caching bugs.

### Recommendation:
**Model A**. `Webkul/Web` permanently owns `/` as an extensible composable canvas. `Website` owns the content that fills `/` via `SectionRegistry`.

---

## 38. Theme Removal Dependency Graph

The exact ordered sequence of dependencies that must be decoupled before `Webkul/Theme` can be deleted:

```text
STEP 1: Audit & Contract (THIS STEP)
  │
  v
STEP 2: Website Presentation & Build Migration
  ├── Move themes/base/assets/css/theme.css -> packages/Webkul/Website/.../website.css
  ├── Establish root / Website Vite build targeting public/build
  ├── Move themes/base master layout into Website as website::layouts.master
  ├── Absorb button and accordion item template improvements into Web
  └── Update Website views to extend website::layouts.master
  │
  v
STEP 3: Decouple Web from Theme Engine
  ├── Remove ThemeResolverContract & ThemeViewFinder from ResolveWebLocale middleware
  ├── Restore Laravel's native FileViewFinder (remove ThemeViewFinder binding)
  ├── Remove activeTheme from WebContext and WebContextContract
  └── Ensure Web fallback layout renders cleanly without themes/base
  │
  v
STEP 4: Remove themes/base
  ├── Update root package.json build scripts to point to new Vite config
  ├── Delete themes/base directory
  └── Re-compile assets to public/build
  │
  v
STEP 5: Delete Webkul/Theme Package & Cleanup
  ├── Remove ThemeServiceProvider from bootstrap/providers.php
  ├── Remove packages/Webkul/Theme directory
  ├── Migrate / remove tests/Feature/Theme/*
  └── Update FoundationOnlyApplicationTest and WebRootHomepageTest
```

---

## 39. File-by-File Migration Matrix

| Current File | Current Owner | Current Responsibility | Current Consumers | Future Owner | Action | Reason |
|---|---|---|---|---|---|---|
| `packages/Webkul/Theme/composer.json` | Theme | Composer metadata | Composer | None | `DELETE_AFTER_MIGRATION` | Theme engine elimination |
| `packages/Webkul/Theme/src/Config/themes.php` | Theme | Config file | `ThemeServiceProvider` | None | `DELETE_AFTER_MIGRATION` | Theme engine elimination |
| `packages/Webkul/Theme/src/Contracts/ThemeRegistryContract.php` | Theme | Registry contract | Service provider, tests | None | `DELETE_AFTER_MIGRATION` | No longer needed |
| `packages/Webkul/Theme/src/Contracts/ThemeResolverContract.php` | Theme | Resolver contract | `ResolveWebLocale`, `WebServiceProvider` | None | `DELETE_AFTER_MIGRATION` | No longer needed |
| `packages/Webkul/Theme/src/Definitions/ThemeDefinition.php` | Theme | Theme value object | Registry, Resolver | None | `DELETE_AFTER_MIGRATION` | No longer needed |
| `packages/Webkul/Theme/src/Exceptions/*.php` (6 files) | Theme | Exceptions | Theme internals | None | `DELETE_AFTER_MIGRATION` | No longer needed |
| `packages/Webkul/Theme/src/Providers/ThemeServiceProvider.php` | Theme | Theme bootstrapper | `bootstrap/providers.php` | None | `DELETE_AFTER_MIGRATION` | Replaced by direct package registration |
| `packages/Webkul/Theme/src/Registry/ThemeRegistry.php` | Theme | In-memory registry | Resolver, tests | None | `DELETE_AFTER_MIGRATION` | No longer needed |
| `packages/Webkul/Theme/src/Resolution/ThemeResolver.php` | Theme | Active theme resolution | Middleware, Web provider | None | `DELETE_AFTER_MIGRATION` | No longer needed |
| `packages/Webkul/Theme/src/View/ThemeViewFinder.php` | Theme | Namespace view overriding | View factory, middleware | Laravel Framework | `REPLACE` (native `FileViewFinder`) | Native view finder is faster & cleaner |
| `themes/base/theme.json` | `themes/base` | Theme manifest | `ThemeDefinition` | None | `DELETE_AFTER_MIGRATION` | No longer needed |
| `themes/base/package.json` | `themes/base` | NPM build script | Root `package.json` | None | `DELETE_AFTER_MIGRATION` | Build moved to root/website |
| `themes/base/vite.config.js` | `themes/base` | Vite build config | Root `package.json` | Root / Website | `REPLACE` | Replaced by root Vite build |
| `themes/base/postcss.config.js` | `themes/base` | PostCSS config | Vite | Website / Root | `MOVE` | PostCSS needed for Tailwind |
| `themes/base/tailwind.config.js` | `themes/base` | Tailwind config | PostCSS | Website / Root | `MOVE` | Tailwind needed for styling |
| `themes/base/assets/css/theme.css` | `themes/base` | CSS tokens & styles | Master layout | `Webkul/Website` | `MOVE` | Visual presentation belongs to Website |
| `themes/base/views/overrides/web/layouts/master.blade.php` | `themes/base` | Master site layout | Web views, Website | `Webkul/Website` | `MOVE` / `REFACTOR` | Becomes `website::layouts.master` |
| `themes/base/views/overrides/web/home/index.blade.php` | `themes/base` | Home section layout | `web::home.index` | `Webkul/Website` | `MOVE` / `REFACTOR` | Move styling to Website sections |
| `themes/base/views/overrides/web/components/button.blade.php` | `themes/base` | Button slot wrapper | `<x-web::button>` | `Webkul/Web` | `REFACTOR` | Merge `<span class="web-button__label">` into Web |
| `themes/base/views/overrides/web/components/accordion/item.blade.php` | `themes/base` | Accordion icon span | `<x-web::accordion.item>`| `Webkul/Web` | `REFACTOR` | Merge CSS icon span into Web |
| `packages/Webkul/Web/src/Http/Middleware/ResolveWebLocale.php` | Web | Locale & direction setup | Web routes middleware | `Webkul/Web` | `REFACTOR` | Remove `ThemeResolver` & `ThemeViewFinder` |
| `packages/Webkul/Web/src/Providers/WebServiceProvider.php` | Web | Web service provider | `bootstrap/providers.php` | `Webkul/Web` | `REFACTOR` | Remove `ThemeResolver` from WebContext |
| `packages/Webkul/Web/src/Context/WebContext.php` | Web | Request context | Views, controllers | `Webkul/Web` | `REFACTOR` | Remove `activeTheme` property & method |
| `packages/Webkul/Web/src/Contracts/WebContextContract.php` | Web | Context contract | Views, controllers | `Webkul/Web` | `REFACTOR` | Remove `activeTheme()` signature |
| `packages/Webkul/Website/src/Providers/WebsiteServiceProvider.php` | Website | Website bootstrapper | `bootstrap/providers.php` | `Webkul/Website` | `REFACTOR` | Replace view composer on `web::layouts.master` |

---

## 40. Boundary Violations Audit

A comprehensive code-scan for boundary violations across packages yielded:

| Boundary Rule | Permitted? | Actual Count | Status | Notes |
|---|---|---|---|---|
| `Web → Website` | **FORBIDDEN** | **0** | **CLEAN** | Web has zero awareness of Website |
| `Web → Student` | **FORBIDDEN** | **0** | **CLEAN** | Web has zero awareness of Student |
| `Web → LostAndFound` | **FORBIDDEN** | **0** | **CLEAN** | Web has zero awareness of Lost & Found |
| `Student → LostAndFound` | **FORBIDDEN** | **0** | **CLEAN** | Student domain is completely independent |
| `LostAndFound → Website` | **FORBIDDEN** | **0** | **CLEAN** | Domain does not know presentation layer |
| `Student → Website` | **FORBIDDEN** | **0** | **CLEAN** | Student domain does not know presentation |
| `Core → Optional Packages` | **FORBIDDEN** | **0** | **CLEAN** | Core has zero knowledge of optional domains |
| `Website → Web` | **ALLOWED** | ~15 | **LEGAL** | Website consumes Web components & registries |
| `Website → LostAndFound Contract` | **ALLOWED** | 1 | **LEGAL** | Consumes readonly `PublicLostAndFoundReadContract` |
| `LostAndFound → Student` | **ALLOWED** | Normal | **LEGAL** | Lost items link to student claims |

---

## 41. Baseline Tests Verification

The test baseline was executed and verified prior to any architectural determinations:

- **PHP Test Suite**:
  - Command: `php artisan test`
  - Total Tests: **608 passed**
  - Total Assertions: **3,932 passed**
  - Duration: **21.98s**
  - Failures: **0**
- **Composer Validation**:
  - Command: `composer validate`
  - Output: `./composer.json is valid`
  - Status: **0 errors, 0 warnings**
- **Configuration Cache**:
  - Command: `php artisan config:cache && php artisan config:clear`
  - Status: **Passes cleanly**
- **Frontend Build**:
  - Command: `npm run build`
  - Output:
    - `public/themes/base/build/manifest.json` (0.40 kB)
    - `public/themes/base/build/assets/theme-BfujC3RQ.css` (39.13 kB)
    - `public/themes/base/build/assets/web-interactions-DaPrZigP.js` (192.14 kB)
  - Duration: **1.83s**
- **Route Count**:
  - Command: `php artisan route:list --json | jq 'length'`
  - Count: **105 routes**

---

## 42. Architectural Risks & Mitigations

1. **Risk: Flash of Unstyled Content (FOUC) or Broken CSS on Theme Removal**:
   - *Risk*: Deleting `themes/base` before Website's build is verified causes all Tailwind utility classes to vanish.
   - *Mitigation*: Sequenced rollout. Website stylesheet and build must be 100% compiled and verified in `public/build` before `themes/base` is unlinked.
2. **Risk: Breaking Foundation-Only Tests**:
   - *Risk*: `FoundationOnlyApplicationTest` specifically asserts `ThemeServiceProvider` and `data-theme="base"`.
   - *Mitigation*: Update Foundation test assertions simultaneously when Web is decoupled from Theme in Step 03.
3. **Risk: Loss of ARIA Accessibility During Component Overrides**:
   - *Risk*: Website developer creates custom modal HTML and omits `role="dialog"` or focus traps.
   - *Mitigation*: Enforcement of Override Model B. Web retains ownership of the outer component and Vue element. Presentation packages only customize slots and styling.
4. **Risk: Breaking Public Web Vue Interactions**:
   - *Risk*: `PublicWebVueKernelTest` asserts `public/themes/base/build/manifest.json`.
   - *Mitigation*: Update the test in Step 02 to check the target manifest path in `public/build/manifest.json`.

---

## 43. Ordered Migration Plan (Subsequent Steps)

The migration path from the current Theme architecture to the target Presentation Package architecture must proceed strictly across the following steps:

### Phase 15 Step 02: Website Presentation & Build Pipeline
1. Create `packages/Webkul/Website/src/Resources/assets/css/website.css` incorporating tokens and styles from `themes/base/assets/css/theme.css`.
2. Configure root `vite.config.js`, `postcss.config.js`, and `tailwind.config.js` targeting `Website` CSS and `Web` JS.
3. Move `themes/base/views/overrides/web/layouts/master.blade.php` into `packages/Webkul/Website/src/Resources/views/layouts/master.blade.php`.
4. Update `Website` views to extend `website::layouts.master` and load assets via `@vite`.
5. Absorb minor button and accordion markup enhancements from `themes/base` into `Webkul/Web`.
6. Verify `npm run build` generates `public/build/manifest.json`.

### Phase 15 Step 03: Decouple Web from Theme
1. Remove `ThemeResolverContract` and `ThemeViewFinder` from `ResolveWebLocale`.
2. Remove `ThemeResolver` injection from `WebServiceProvider`.
3. Remove `activeTheme` from `WebContext` and `WebContextContract`.
4. Restore standard Laravel `FileViewFinder`.
5. Verify Web components and fallback layout render without Theme.

### Phase 15 Step 04: Remove `themes/base`
1. Update root `package.json` scripts (`"dev": "vite"`, `"build": "vite build"`).
2. Physically delete `themes/base/`.
3. Remove `public/themes/base/build/`.
4. Verify all tests pass.

### Phase 15 Step 05: Delete `Webkul/Theme` & Certify
1. Remove `Webkul\Theme\Providers\ThemeServiceProvider` from `bootstrap/providers.php`.
2. Physically delete `packages/Webkul/Theme/`.
3. Remove `tests/Feature/Theme/` test suite.
4. Update `FoundationOnlyApplicationTest`, `WebRootHomepageTest`, and `PublicWebVueKernelTest`.
5. Run full test suite (`php artisan test`) and verify 100% pass rate.

---

## 44. Blockers Preventing Immediate Theme Deletion

Immediate deletion of `Webkul/Theme` and `themes/base` is currently blocked by **7 physical blockers**:

1. **Blocker 1 (`ResolveWebLocale` Middleware)**: Type-hints `ThemeResolverContract` in constructor and calls `ThemeViewFinder::setActiveThemeChain()` on every request. Deleting Theme causes container resolution fatal errors.
2. **Blocker 2 (`WebServiceProvider`)**: Injects `ThemeResolverContract` to construct `WebContext`.
3. **Blocker 3 (`WebContextContract`)**: Requires `activeTheme(): string`.
4. **Blocker 4 (`view.finder` Container Binding)**: `ThemeServiceProvider` replaces Laravel's `view.finder` singleton with `ThemeViewFinder`. Removing the provider without checking view discovery paths would break custom override resolution.
5. **Blocker 5 (`CSS Assets`)**: The entire CSS styling (`assets/css/theme.css`, 760 lines) lives inside `themes/base/assets/css/theme.css`. Deleting `themes/base` deletes all styles for the application.
6. **Blocker 6 (`Vite Build Pipeline`)**: Root `package.json` hardcodes `vite --config themes/base/vite.config.js`. Deleting `themes/base` immediately breaks `npm run build` and CI assets.
7. **Blocker 7 (`Test Assertions`)**: 89 test assertions across `tests/Feature/Theme/*`, `FoundationOnlyApplicationTest`, `WebRootHomepageTest`, and `PublicWebVueKernelTest` assert Theme engine classes and `data-theme="base"`.

---

## 45. Final Recommendation

**Do not delete `Webkul/Theme` or `themes/base` in this step.**

Proceed deterministically to **Phase 15 Step 02**:
- Migrate Website's CSS, master layout, and build pipeline into `Webkul/Website` and the root Vite orchestrator.
- Once Website is self-sufficient with its own verified build output, proceed to decouple `Webkul/Web`, delete `themes/base`, and finally delete `Webkul/Theme`.

---

## 46. Machine-Readable Certification

```ini
PHASE_15_STEP_01_STATUS=COMPLETE
SOURCE_AUDIT_COMPLETE=YES
PRODUCTION_CHANGES_MADE=NO

THEME_PACKAGE_FILES=15
BASE_THEME_FILES=10
WEB_FILES=42
WEBSITE_FILES=28

WEB_THEME_REFERENCE_COUNT=6
WEBSITE_THEME_REFERENCE_COUNT=0
ROOT_THEME_REFERENCE_COUNT=92

WEB_TO_WEBSITE_REFS=0
WEB_TO_STUDENT_REFS=0
WEB_TO_LOST_FOUND_REFS=0
STUDENT_TO_LOST_FOUND_REFS=0
LOST_FOUND_TO_WEBSITE_REFS=0

WEB_COMPONENT_COUNT=14
WEB_STATIC_COMPONENT_COUNT=9
WEB_INTERACTIVE_COMPONENT_COUNT=5

VUE_VERSION=3.5.43
CREATE_APP_COUNT=1
VUE_MOUNT_TARGET=#app
LEGACY_INTERACTION_PATHS=2

BAGISTO_COMPONENTS_AUDITED=22
BAGISTO_GENERIC_CANDIDATES=15
BAGISTO_DOMAIN_COMPONENTS_REJECTED=7

PRESENTATION_OVERRIDE_MODEL=MODEL_B_LOGIC_WRAPPER_WITH_VISUAL_PARTIAL
LAYOUT_OWNER=WEBSITE_PRESENTATION_PACKAGE
SITE_DEFINITION_OWNER=WEBKUL_WEBSITE
NAVIGATION_REGISTRY_OWNER=WEBKUL_WEB
SECTION_REGISTRY_OWNER=WEBKUL_WEB

CURRENT_FRONTEND_BUILD_OWNER=THEMES_BASE
TARGET_FRONTEND_BUILD_OWNER=WEBKUL_WEBSITE_AND_ROOT_VITE
FOUNDATION_ONLY_BUILD_STRATEGY=SEMANTIC_FALLBACK_CSS_AND_PRECOMPILED_RUNTIME

ROOT_ROUTE_CURRENT_OWNER=WEBKUL_WEB
ROOT_ROUTE_TARGET_OWNER=WEBKUL_WEB_VIA_SECTION_REGISTRY

THEME_REMOVAL_READY=NO
THEME_REMOVAL_BLOCKER_COUNT=7

BASELINE_PHP_TESTS=608
BASELINE_PHP_ASSERTIONS=3932
FRONTEND_BUILD=PASS
COMPOSER_VALIDATE=PASS
CONFIG_CACHE=PASS

NEXT_STEP=PHASE_15_STEP_02_WEBSITE_PRESENTATION_AND_BUILD_MIGRATION
```
