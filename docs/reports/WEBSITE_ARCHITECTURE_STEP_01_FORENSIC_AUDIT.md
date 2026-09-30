# CampusHub — Website Architecture: Step 01 Forensic Audit & Boundary Design

**Date:** 2026-09-29  
**Mode:** FORENSIC AUDIT + ARCHITECTURE DESIGN ONLY (Report-Only Step)  
**Status:** COMPLETE  

---

## 1. Executive Summary

A forensic physical audit of the CampusHub repository was conducted to establish the exact boundaries, contracts, and interaction models for a future optional `Website` package.

The primary architectural outcome is:
1. **`Webkul\Web` is permanent Foundation**: It provides generic public-web runtime context, navigation infrastructure, section registration, SEO metadata services, UI primitives, and the default fallback landing page. **`Web` works fully without `Website`**.
2. **`Webkul\Theme` is generic presentation infrastructure**: It owns theme discovery, inheritance chain resolution, and view overrides via `ThemeViewFinder`. It has zero package dependencies and zero business awareness.
3. **`themes/base` (Base Theme) is the default safe presentation**: It restyles Web primitives, provides responsive RTL/LTR layouts, and ensures that in the absence of any custom website package or custom theme, the public portal is fully operational and styled.
4. **`Website` is an optional composition package**: Conceptually positioned as a site-specific presentation and composition package. It does not replace `Web` or `Theme`; instead, it consumes `Web` infrastructure, registers site-specific navigation/sections, optionally pairs with a site-specific theme, and consumes business packages (e.g. `Event`, `Student`) via deliberate read contracts.
5. **Strict Removability & Fallback**: When `Website` is uncomposed or removed, the system restores the default Web and Base theme presentation immediately, without modifying any Foundation package, business package, or database state. Zero `class_exists()` or runtime flag checks are required.

---

## 2. Rules Reviewed

The following architectural documents and rules were physically inspected and strictly adhered to:

- [`docs/rules/README.md`](file:///home/hosam/Documents/CampusHub-main/docs/rules/README.md): Conceptual hierarchy and authority of repository rules.
- [`docs/rules/08_SEED_FOUNDATION_AND_PACKAGE_ISOLATION_RULES.md`](file:///home/hosam/Documents/CampusHub-main/docs/rules/08_SEED_FOUNDATION_AND_PACKAGE_ISOLATION_RULES.md): Foundation vs Optional package classification, dependency direction (`Foundation -> Optional` forbidden, `Optional -> Foundation` allowed), package-owned registration, removability laws, and deployment composition.
- [`docs/rules/09_PACKAGE_INTERNAL_ARCHITECTURE_AND_EXTENSION_RULES.md`](file:///home/hosam/Documents/CampusHub-main/docs/rules/09_PACKAGE_INTERNAL_ARCHITECTURE_AND_EXTENSION_RULES.md): Package-internal architecture, canonical layers, composition roots, event/listener ownership, and extension mechanisms.
- [`docs/rules/10_ADMIN_WEB_PRESENTATION_BOUNDARY_RULES.md`](file:///home/hosam/Documents/CampusHub-main/docs/rules/10_ADMIN_WEB_PRESENTATION_BOUNDARY_RULES.md): Strict separation between `Admin` and `Web` pipelines, generic public root ownership by `Web`, and request-locale-safe public navigation.
- [`docs/rules/11_PERSISTENCE_AND_NO_UNDO_RULES.md`](file:///home/hosam/Documents/CampusHub-main/docs/rules/11_PERSISTENCE_AND_NO_UNDO_RULES.md): Mandate that tested implementations remain on disk without undo/reverts.
- [`docs/rules/12_WEB_COMPONENT_KERNEL_AND_INTERACTION_RULES.md`](file:///home/hosam/Documents/CampusHub-main/docs/rules/12_WEB_COMPONENT_KERNEL_AND_INTERACTION_RULES.md): Ownership of public UI primitives by `Web`, isolation from Admin, zero external JS frameworks, progressive enhancement, and data-attribute behavioral selectors.
- [`docs/rules/13_BASE_THEME_PRESENTATION_RULES.md`](file:///home/hosam/Documents/CampusHub-main/docs/rules/13_BASE_THEME_PRESENTATION_RULES.md): Production web theme presentation rules, visual token ownership, and override contracts.
- [`docs/architecture/FOUNDATION_ARCHITECTURE.md`](file:///home/hosam/Documents/CampusHub-main/docs/architecture/FOUNDATION_ARCHITECTURE.md): Foundation classification, proven dependency graph, and the Foundation-hardening development baseline.
- Prior Phase 13 reports:
  - [`docs/reports/PHASE_13_STEP_01_FOUNDATION_PACKAGE_LIFECYCLE_AUDIT.md`](file:///home/hosam/Documents/CampusHub-main/docs/reports/PHASE_13_STEP_01_FOUNDATION_PACKAGE_LIFECYCLE_AUDIT.md)
  - [`docs/reports/PHASE_13_STEP_02A_FOUNDATION_DECOUPLING_REPORT.md`](file:///home/hosam/Documents/CampusHub-main/docs/reports/PHASE_13_STEP_02A_FOUNDATION_DECOUPLING_REPORT.md)
  - [`docs/reports/PHASE_13_STEP_02B_OPTIONAL_PACKAGE_COMPOSITION_REPORT.md`](file:///home/hosam/Documents/CampusHub-main/docs/reports/PHASE_13_STEP_02B_OPTIONAL_PACKAGE_COMPOSITION_REPORT.md)
  - [`docs/reports/PHASE_13_STEP_03_FOUNDATION_ONLY_BASELINE_REPORT.md`](file:///home/hosam/Documents/CampusHub-main/docs/reports/PHASE_13_STEP_03_FOUNDATION_ONLY_BASELINE_REPORT.md)

---

## 3. Current Web Ownership

Physical inspection of [`packages/Webkul/Web`](file:///home/hosam/Documents/CampusHub-main/packages/Webkul/Web):

### Production Surface
- **Composer Manifest**: [`packages/Webkul/Web/composer.json`](file:///home/hosam/Documents/CampusHub-main/packages/Webkul/Web/composer.json)
  - Requires: `php: ^8.2`, `illuminate/support: ^11.0|^12.0`, `krayin/laravel-core: ^1.0`, `webkul/theme: dev-main`.
- **Main Service Provider**: [`packages/Webkul/Web/src/Providers/WebServiceProvider.php`](file:///home/hosam/Documents/CampusHub-main/packages/Webkul/Web/src/Providers/WebServiceProvider.php)
  - Registers singletons for `NavigationRegistryContract`, `SectionRegistryContract`, and `SeoMetadataContract`.
  - Binds scoped `WebContextContract` (locale, direction, active theme).
  - Aliases middleware `web_locale` and `web_context` to `ResolveWebLocale`.
  - Loads translations (`web`), views (`web`), and routes (`Routes/web-routes.php`).
- **Middleware**: [`packages/Webkul/Web/src/Http/Middleware/ResolveWebLocale.php`](file:///home/hosam/Documents/CampusHub-main/packages/Webkul/Web/src/Http/Middleware/ResolveWebLocale.php)
  - Resolves request locale from session/cookie or defaults to primary content locale via `ContentLocaleService`.
  - Configures active theme inheritance chain on `ThemeViewFinder`.
  - Instantiates `WebContext` and shares `$webContext` with all views.
- **Routing**: [`packages/Webkul/Web/src/Routes/web-routes.php`](file:///home/hosam/Documents/CampusHub-main/packages/Webkul/Web/src/Routes/web-routes.php)
  - `GET /` -> `HomeController@index` (`web.home`).
  - `GET|POST web/locale/{code}` -> `LocaleController@switch` (`web.locale.switch`).
- **Controllers**:
  - [`HomeController.php`](file:///home/hosam/Documents/CampusHub-main/packages/Webkul/Web/src/Http/Controllers/HomeController.php): Sets default SEO metadata, queries `SectionRegistryContract->getSections('home')`, and renders `web::home.index`.
  - [`LocaleController.php`](file:///home/hosam/Documents/CampusHub-main/packages/Webkul/Web/src/Http/Controllers/LocaleController.php): Validates requested locale code and updates session/cookie.
- **Registries & Contracts**:
  - `WebContextContract` / `WebContext`: Encapsulates locale, direction (RTL/LTR), active theme ID, and canonical URL.
  - `NavigationRegistryContract` / `NavigationRegistry`: Thread-safe, location-based (`header`, `footer`, `mobile`, `secondary`) menu registry supporting hierarchical trees.
  - `NavigationLabelResolver`: Translates labels during request execution using `NavigationLabel::translation('key')`.
  - `SectionRegistryContract` / `SectionRegistry`: Keyed and ordered page section aggregator supporting deferred data closures and visibility predicates.
  - `SeoMetadataContract` / `SeoService`: Manages page title, meta description, OpenGraph, Twitter tags, and canonical links.
- **UI Component Kernel**: Headless, accessible Blade primitives:
  - `<x-web::accordion>`, `<x-web::accordion.item>`, `<x-web::alert>`, `<x-web::badge>`, `<x-web::button>`, `<x-web::card>`, `<x-web::form.field>`, `<x-web::form.input>`.
- **Assets**: [`packages/Webkul/Web/src/Resources/assets/js/web-interactions.js`](file:///home/hosam/Documents/CampusHub-main/packages/Webkul/Web/src/Resources/assets/js/web-interactions.js) (delegated event listeners for accordion toggle and alert dismissal).
- **Views**:
  - [`layouts/master.blade.php`](file:///home/hosam/Documents/CampusHub-main/packages/Webkul/Web/src/Resources/views/layouts/master.blade.php): Semantic HTML shell with `@yield('header')`, `@yield('content')`, `@yield('footer')`, `@stack('styles')`, `@stack('scripts')`.
  - [`home/index.blade.php`](file:///home/hosam/Documents/CampusHub-main/packages/Webkul/Web/src/Resources/views/home/index.blade.php): Renders empty state or iterates over registered sections.

### Answers to Questions W1–W5
- **W1 (Current Responsibilities)**: Web runtime context, request-locale determination, theme-finder activation, navigation aggregation, page section aggregation, SEO metadata generation, accessible UI primitives, progressive client interactions, default fallback master layout, default root route `/`, and locale-switching endpoint.
- **W2 (Generic Web Foundation Responsibilities)**: `WebContextContract`, `ResolveWebLocale` middleware, `ThemeViewFinder` hook, `NavigationRegistryContract`, `SectionRegistryContract`, `SeoMetadataContract`, component kernel, `web-interactions.js`, and layout extension points.
- **W3 (Default-Site Responsibilities)**: The default welcome message in `HomeController@index` / `web::home.index`, default static navigation translation keys, and default route binding of `/`.
- **W4 (Customization Assumptions in Web)**: Web contains **zero** assumptions preventing customization. In fact, `SectionRegistryContract` and `ThemeViewFinder` were intentionally architected to allow complete visual and structural customization without modifying Web source.
- **W5 (Business Package Dependencies)**:
  - Scans for `Student`, `Event`, `LostAndFound`, `Shop`:
    - Business imports / class references: **0**
    - Business queries / DB calls: **0**
    - Business route dependencies: **0**
    - Coincidental strings: 4 DOM event references in JS (`event.target`, `addEventListener('click')`), and 2 static translation keys (`'events' => 'Events'`, `'lost_and_found' => 'Lost & Found'`) in `Resources/lang/*/app.php`. These translation keys are not referenced anywhere in Web code and represent benign static residue.

---

## 4. Current Theme Ownership

Physical inspection of [`packages/Webkul/Theme`](file:///home/hosam/Documents/CampusHub-main/packages/Webkul/Theme):

### Production Surface
- **Composer Manifest**: [`packages/Webkul/Theme/composer.json`](file:///home/hosam/Documents/CampusHub-main/packages/Webkul/Theme/composer.json)
  - Contains **empty require block** (completely decoupled library).
- **Configuration**: [`packages/Webkul/Theme/src/Config/themes.php`](file:///home/hosam/Documents/CampusHub-main/packages/Webkul/Theme/src/Config/themes.php)
  - `active` defaults to `env('APP_THEME', 'base')`.
  - `fallback` defaults to `'base'`.
  - `paths` scans `[base_path('themes')]`.
- **Theme Engine Architecture**:
  - `ThemeDefinition`: DTO describing id, name, parent theme, views path, assets path, version.
  - `ThemeRegistry`: Discovers themes by scanning directory trees for `theme.json`, registers definitions, and resolves parent-child inheritance chains while checking for cycles (`ThemeInheritanceCycleException`).
  - `ThemeResolver`: Evaluates active theme for the request context (respecting explicit overrides, `themes.active`, `themes.fallback`, and a final fallback to `resource_path('views')`).
  - `ThemeViewFinder`: Extends Laravel's `FileViewFinder`. Intercepts namespaced view resolution (`namespace::view.name`) and searches `{themeViewsPath}/overrides/{namespace}/{view}.blade.php` up the inheritance chain before falling back to the package's original view path. Strictly protects `admin`, `mail`, `notifications`, and `errors` from overrides.
  - `ThemeServiceProvider`: Binds registry and resolver singletons, discovers themes, and substitutes `view.finder` with `ThemeViewFinder`.

### Answers to Questions T1–T5
- **T1 (Is Theme generic?)**: Yes, 100% generic.
- **T2 (Does Theme know Business Packages?)**: No. Exact scan count: **0** references to Student, Event, LostAndFound, or Shop.
- **T3 (Site-Specific Behavior in Theme)**: None. It is purely an engine for discovering themes from disk and resolving view paths.
- **T4 (Theme Selection Contract)**: `Webkul\Theme\Contracts\ThemeResolverContract` selects the active theme from request runtime state or `config('themes.active')`, with `config('themes.fallback')` providing deterministic fallback.
- **T5 (Fallback Safety when Website is absent)**: Yes. When Website is absent, the fallback chain resolves to `base` (in `themes/base`), guaranteeing that every Web view resolves safely.

---

## 5. Base Theme Ownership

Physical inspection of [`themes/base`](file:///home/hosam/Documents/CampusHub-main/themes/base):

- **Manifest**: [`theme.json`](file:///home/hosam/Documents/CampusHub-main/themes/base/theme.json) (`id: "base"`, `name: "Base"`, `parent: null`, `views_path: "views"`, `assets_path: "assets"`).
- **Assets**: [`assets/css/theme.css`](file:///home/hosam/Documents/CampusHub-main/themes/base/assets/css/theme.css) compiled to `public/themes/base/build/assets/theme-*.css`. Supplies design tokens (colors, typography, borders, shadows) and bidirectional (RTL/LTR) rules.
- **View Overrides**:
  - `views/overrides/web/layouts/master.blade.php`: Injects `SeoMetadataContract`, `NavigationRegistryContract`, and `NavigationLabelResolver`. Renders header, mobile, and footer navigation dynamically from registry items. Renders `@yield('content')`.
  - `views/overrides/web/home/index.blade.php`: Styled presentation of the home page, iterating through `$sections` or displaying the styled empty state.
  - `views/overrides/web/components/accordion/item.blade.php`: Overrides visual styles while preserving `data-web-*` behavior hooks.
  - `views/overrides/web/components/button.blade.php`: Overrides visual styles while preserving semantic element and attributes.
- **Business Concepts in Base**: **0**. Exactly 0 references to Student, Event, LostAndFound, or Shop.
- **Default Safe Presentation Verdict**: Base theme is completely safe, decoupled, and fully equipped to serve as the permanent default presentation when `Website` is absent.

---

## 6. Current Event Public-Web Ownership

Physical inspection of [`packages/Webkul/Event`](file:///home/hosam/Documents/CampusHub-main/packages/Webkul/Event):

### Production Footprint
- **Public Routes**: [`packages/Webkul/Event/src/Routes/web-routes.php`](file:///home/hosam/Documents/CampusHub-main/packages/Webkul/Event/src/Routes/web-routes.php)
  - `GET events` -> `EventController@index` (`event.web.index`).
  - `GET events/{event}` -> `EventController@show` (`event.web.show`).
  - Uses `['web', 'web_context']` middleware pipeline.
- **Public Controller**: [`packages/Webkul/Event/src/Http/Controllers/Web/EventController.php`](file:///home/hosam/Documents/CampusHub-main/packages/Webkul/Event/src/Http/Controllers/Web/EventController.php)
  - `index()`: Calls `EventRepository->paginatePublic()`, populates SEO title/description, returns `event::web.index`.
  - `show()`: Calls `EventRepository->findPublicOrFail()`, populates SEO title/description, returns `event::web.show`.
- **Public Views**:
  - `packages/Webkul/Event/src/Resources/views/web/index.blade.php`: Grid of event cards consuming `<x-web::card>`, `<x-web::badge>`, `<x-web::button>`, with pagination.
  - `packages/Webkul/Event/src/Resources/views/web/show.blade.php`: Event details, organizer, dates, seat availability, images.
- **Public Navigation**:
  - `EventServiceProvider::registerWebNavigation()` registers `event.events` in `NavigationRegistryContract` for `'header'` and `'mobile'` with `NavigationLabel::translation('event::web.navigation.events')`.
- **Public Read Repositories**:
  - `EventRepository::paginatePublic()`: Applies `published()` domain scope, selects public attributes, orders by `event_date`, applies pagination.
  - `EventRepository::findPublicOrFail()`: Applies `published()` domain scope, eager loads public images, fails if not found or private.
- **Dependency**:
  - `packages/Webkul/Event/composer.json` declares `"webkul/web": "dev-main"`. (Permitted by Rule 08: Optional Package -> Foundation is allowed).

### Responsibility Classification
Under the proposed architecture:
1. **Belongs to `Event` Package**:
   - Domain entity, database tables, migrations, availability business invariants, seat calculations.
   - Public read methods / contracts (`paginatePublic()`, `findPublicOrFail()`).
   - Domain events (`EventPublished`, etc.).
   - Default self-contained public pages (`/events`, `/events/{id}`) and views (`event::web.*`) so Event remains an autonomous, functional package even when no custom `Website` package is installed.
2. **Belongs to Site-Specific `Website` Package**:
   - Composing an "Upcoming Events" section on the university or corporate homepage (via `SectionRegistryContract`).
   - Customizing navigation placement (e.g. nesting Events under "Student Life" or "Campus Activities").
   - Site-specific composite widgets (e.g. merging Event cards with department banners or campus map links).
   - Branding / styling overrides for Event views (via a site theme).

---

## 7. Existing Dependency Graph

Based on physical inspection of all Composer manifests and Phase 13 composition records:

```text
               ┌─────────────┐
               │    Core     │
               └──────┬──────┘
                      │
       ┌──────────────┼──────────────┐
       │              │              │
       ▼              ▼              ▼
┌─────────────┐┌─────────────┐┌─────────────┐
│    User     ││  DataGrid   ││    Theme    │
└──────┬──────┘└──────┬──────┘└──────┬──────┘
       │              │              │
       ├──────────────┤              │
       ▼              │              ▼
┌─────────────┐       │       ┌─────────────┐
│  Installer  │       │       │     Web     │
└─────────────┘       │       └──────┬──────┘
                      │              │
       ┌──────────────┘              │
       ▼                             │
┌─────────────┐                      │
│    Admin    │                      │
└─────────────┘                      │
                                     │
═════════════════════════════════════╪═════════════════════════════════════
FOUNDATION (Always Composed)         │
─────────────────────────────────────┼─────────────────────────────────────
OPTIONAL FEATURES (Deployment-Flag)  │
                                     │
              ┌──────────────────────┤
              │                      │
              ▼                      ▼
       ┌─────────────┐        ┌─────────────┐
       │   Student   │◄───────┤    Event    │
       └──────┬──────┘        └─────────────┘
              ▲
              │
       ┌──────┴──────┐
       │LostAndFound │
       └─────────────┘
```

**Observations:**
- Foundation packages (`Core`, `User`, `Admin`, `DataGrid`, `Installer`, `Web`, `Theme`) form an acyclic graph with zero references to Optional packages.
- `Event` depends on `Student` (optional-to-optional) and `Web` (optional-to-foundation).
- Foundation-only mode boots without `Student`, `Event`, or `LostAndFound`.

---

## 8. Proposed Website Definition

`Website` is defined as:
> **An optional, site-specific presentation and composition package that assembles pages, navigation, homepage sections, and business package integrations into a cohesive public portal.**

### Key Principles
1. **Strictly Optional**: Absence of `Website` leaves the system in a valid, functional state (Default Web + Base Theme).
2. **Non-Foundational**: It is not required by `Web`, `Theme`, `Admin`, or any business package.
3. **No Domain Logic**: It owns no database tables, no entity migrations, and no business transactions. It consumes public read contracts and services exposed by business packages.
4. **Site-Tailored**: Each deployment may have its own Website package (e.g. `UniversityWebsite`, `CorporateWebsite`) or use the default `Website` implementation.

---

## 9. Web vs Theme vs Website vs Business Boundary

| Capability | Web (Foundation) | Theme (Foundation) | Base (Theme Impl) | Website (Optional Pkg) | Business Pkg (Optional) |
|---|---|---|---|---|---|
| **Runtime & Pipeline** | OWNS (`WebContext`, middleware) | NO (consumed by Web) | NO (appearance only) | CONSUMES (via Web) | CONSUMES (via Web) |
| **View Lookup & Overrides** | CONSUMES (`ThemeViewFinder`) | OWNS (`ThemeViewFinder`) | SUBJECT TO (resolved by Theme) | CONSUMES / EXTENDS | SUBJECT TO (views overridable) |
| **Theme Inheritance** | NO | OWNS (`ThemeRegistry`) | IMPLEMENTS (root theme) | MAY CONFIGURE | NO |
| **CSS Tokens & Styles** | NO (unstyled primitives) | MANAGES (assets path) | OWNS (`theme.css`) | DELEGATES TO THEME | NO (or default styles) |
| **Generic Components** | OWNS (`button`, `card`, etc.) | RESTYLES (tokens/classes) | RESTYLES | CONSUMES (`<x-web::*>`) | CONSUMES (`<x-web::*>`) |
| **Site-Specific Components** | FORBIDDEN | FORBIDDEN | FORBIDDEN | OWNS (`<x-website::*>`) | FORBIDDEN |
| **Generic Root `/` Route** | OWNS (`web.home`) | FORBIDDEN | OVERRIDES (presentation) | COMPOSES (sections/view) | FORBIDDEN |
| **Site-Specific Routes** | FORBIDDEN | FORBIDDEN | FORBIDDEN | OWNS (`/about`, `/contact`) | OWNS (`/events`, etc.) |
| **Homepage Composition** | OWNS (`SectionRegistry`) | NO | RESTYLES DEFAULT | REGISTERS SECTIONS | MAY CONTRIBUTE SECTION |
| **Navigation Composition** | OWNS (`NavigationRegistry`) | RENDERS ITEMS | RENDERS ITEMS | ASSEMBLES SITE NAV | REGISTERS OWN ENTRY |
| **Domain Logic & Entities** | FORBIDDEN | FORBIDDEN | FORBIDDEN | FORBIDDEN | OWNS (models, rules, DB) |
| **Public Read Queries** | FORBIDDEN | FORBIDDEN | FORBIDDEN | CONSUMES CONTRACTS | OWNS (implements queries) |

---

## 10. Events and Listeners Ownership

The repository-wide law for event-driven interaction is:
```text
Publisher owns the event and its payload.
Consumer owns the listener and its reaction.
```

- If `Event` emits a domain event (e.g. `Webkul\Event\Events\EventPublished`):
  - `Event` owns the event class, timing, and payload.
  - `Website` MAY register a listener (e.g. `Webkul\Website\Listeners\PurgeUpcomingEventsCache`).
  - `Event` does not know `Website` or its listener exists.
- If `Website` is uninstalled or removed:
  - The listener disappears with `Website`.
  - `Event` continues publishing its event without error.
- **Current Repository Status**: Fully supported. Laravel’s event dispatcher silently ignores events when no listeners are bound.

---

## 11. Presentation Extension Analysis

A comparative evaluation was performed against Laravel package patterns and Bagisto presentation mechanisms:

| Extension Mechanism | Bagisto Pattern | CampusHub Web Status | Verdict for Website |
|---|---|---|---|
| **View Render Events (`view_render_event()`)** | Heavy use in shop views for third-party hook injection | Used extensively in `Admin`, but **0 calls in `Web` and `Base`** | **UNNECESSARY / NOT RECOMMENDED** |
| **Section Registry (`SectionRegistryContract`)** | Not in standard Bagisto | Native to `Webkul\Web`; allows ordering, closures, view inclusion | **RECOMMENDED (Primary Composition Mechanism)** |
| **Navigation Registry (`NavigationRegistryContract`)** | Config-based menu arrays | Native to `Webkul\Web`; request-locale safe, dynamic trees | **RECOMMENDED (Primary Nav Mechanism)** |
| **Theme View Finder (`overrides/{namespace}/*`)** | Core Bagisto theme system | Native to `Webkul\Theme`; protected namespaces, fallback | **RECOMMENDED (Primary Visual Override Mechanism)** |
| **Blade Components (`<x-web::*>`)** | Partial Blade / Vue mix | Native to `Webkul\Web`; headless, accessible, strict a11y | **RECOMMENDED (UI Primitives Foundation)** |

### Verdict on View Render Events for Web
**`UNNECESSARY`**: Introducing `view_render_event()` into `Web` is unnecessary and ill-advised.
1. `SectionRegistryContract` already provides structured, typed, orderable page section composition.
2. `ThemeViewFinder` already provides template-level overrides without monkey-patching.
3. Arbitrary string injection via view render events violates Rule 12's accessibility, progressive enhancement, and component contract standards.

---

## 12. Website / Theme Architecture Models

Three conceptual models were evaluated for integrating `Website` with `Theme`:

### Model A: Independent Parallel Presentation
`Web` branches into `Theme` OR `Website`.
- *Analysis*: Forces `Website` to duplicate view resolution, theme inheritance, and asset compilation, or bypasses `Theme` entirely.
- *Verdict*: **REJECTED** (high duplication, bifurcated rendering engine).

### Model B: Website Uses Theme Infrastructure
`Web` -> `Theme Engine` -> `Website` builds on Theme infrastructure.
- *Analysis*: `Theme` remains the generic asset, token, and view finder engine. `Website` is an optional PHP package providing routes, controllers, navigation/section composition, and site-specific widgets, pairing with a theme (e.g. `themes/university`).
- *Verdict*: **HIGHLY VIABLE**.

### Model C: Hybrid Composition Package + Paired Theme
`Website` is a composition package that registers backend structures (routes, controllers, sections, navigation) while delegating styling and template overrides to an accompanying theme directory (or `themes/base`).
- *Analysis*: Maximizes removability. Removing `Website` package leaves `Theme` intact, reverting presentation cleanly to default `Web` + `Base`.
- *Verdict*: **HIGHLY VIABLE**.

---

## 13. Recommended Model

**RECOMMENDED: Model B/C Hybrid ("Website as Composition Package leveraging Theme Engine")**.

### Architecture:
1. **`Webkul\Web`**: Permanent public foundation. Owns `/`, middleware, context, registries, components.
2. **`Webkul\Theme`**: Permanent generic presentation engine. Owns `ThemeViewFinder`, inheritance, asset discovery.
3. **`themes/base`**: Permanent default safe theme.
4. **`Webkul\Website`**: Optional composition package.
   - Registers site pages (`/about`, `/contact`, `/admissions`).
   - Contributes homepage sections into `SectionRegistryContract` (`'home'`).
   - Configures site navigation into `NavigationRegistryContract`.
   - Owns site-specific composite Blade components (`<x-website::*>`).
   - Consumes business packages via public read contracts.
   - Pairs with a dedicated theme (e.g. `themes/campus` inheriting from `themes/base`) or styles default components.

---

## 14. Optional Package Composition Integration

Under Phase 13's deployment composition standard:
- **Classification**: `OPTIONAL` (never Foundation).
- **Manifest Integration**: `packages/Webkul/Website/composer.json` declares:
  ```json
  "extra": {
      "campushub": {
          "id": "website",
          "type": "optional",
          "provider": "Webkul\\Website\\Providers\\WebsiteServiceProvider",
          "concord_module": null
      }
  }
  ```
- **Configuration Integration**: Added to `$catalog` paths in `config/campushub.php`.
- **Activation**: Enabled via `CAMPUSHUB_OPTIONAL_PACKAGES=website` (or `website,student,event`).
- **No Secondary System**: Zero database toggles, zero admin switches, zero dynamic package discovery.

---

## 15. Dependency Strategy

### Infrastructure Dependencies
- `Website -> Webkul\Web`: **HARD** (declared in `composer.json`).
- `Website -> Webkul\Core`: **HARD** (declared in `composer.json`).
- `Website -> Webkul\Theme`: **HARD** or consumed transitively via `Web`.

### Business Package Dependencies
- If a site (e.g. `CampusWebsite`) requires `Event`:
  - `composer.json` declares `"webkul/event": "dev-main"`.
  - `OptionalPackageManifestLoader` automatically reads this requirement and adds `event` to `requires`.
  - `OptionalPackageComposition` enforces that if `website` is enabled, `event` must also be enabled.
- If a site does not require `Event` (e.g. `CorporateWebsite`):
  - Its manifest does not require `webkul/event`.
  - It boots without `Event`.
- **Hidden Dependencies Forbidden**: Calling `EventRepository` without declaring `webkul/event` in `composer.json` is strictly prohibited by Rule 08.

---

## 16. Removability Matrix

Evaluation across all architectural states:

| State | Composition | Boot | Root `/` | Default Presentation | Website Customization | Business Packages | Public Package Presentation |
|---|---|---|---|---|---|---|---|
| **A** | Web + Theme/Base (Website Absent) | PASS | PASS (`web.home`) | PASS (Base Theme) | ABSENT | NONE (Disabled) | NONE |
| **B** | Web + Theme/Base + Website (No Business) | PASS | PASS (Website Home) | PASS (Site/Base Theme) | PASS (Pages, Nav) | NONE (Disabled) | NONE |
| **C** | Web + Theme/Base + Website + Event | PASS | PASS (Home with Events) | PASS (Site/Base Theme) | PASS | PASS (`Event` Active) | PASS (`/events`, `/events/{id}`) |
| **D** | Web + Theme/Base + Website + Student + Event | PASS | PASS | PASS | PASS | PASS (`Student`, `Event`) | PASS (Auth, Events) |
| **E** | Website Removed (Reverted to Base) | PASS | PASS (`web.home` restored) | PASS (Base Theme restored) | ABSENT (Cleanly Gone) | Intact if enabled | Intact if enabled |
| **F** | Event Disabled while Website Requires It | REJECTED at config load | N/A | N/A | N/A | N/A | N/A (Deterministic Failure) |
| **G** | Website Disabled while Event Remains Enabled | PASS | PASS (`web.home`) | PASS (Base Theme) | ABSENT | PASS (`Event` Active) | PASS (`/events` via Event's default Web) |

---

## 17. Failure Isolation

1. **Website Absent**:
   - `OptionalPackageComposition` does not include Website provider.
   - Web boots with default `HomeController` and Base theme.
   - Result: **CLEAN FALLBACK PASS**.
2. **Website Enabled but Dependency Missing**:
   - If Website requires `event`, but `CAMPUSHUB_OPTIONAL_PACKAGES` omits `event`:
   - `OptionalPackageComposition` throws `InvalidPackageComposition`:
     `Optional package "website" requires enabled package "event".`
   - Result: **FAST DETERMINISTIC COMPOSITION FAILURE** (prevents broken runtime).
3. **Distinction**:
   - `Website absent` = valid default operating state.
   - `Website enabled with unmet dependencies` = invalid configuration error.

---

## 18. Proposed Package Shape

Derived from CampusHub conventions (strictly non-empty layers only):

```text
packages/Webkul/Website/
├── composer.json
├── ARCHITECTURE.md
└── src/
    ├── Config/
    │   └── website.php
    ├── Http/
    │   ├── Controllers/
    │   │   └── PageController.php
    │   └── Middleware/ (if site-specific middleware needed)
    ├── Providers/
    │   └── WebsiteServiceProvider.php
    ├── Resources/
    │   ├── lang/
    │   │   ├── ar/
    │   │   │   └── app.php
    │   │   └── en/
    │   │       └── app.php
    │   └── views/
    │       ├── pages/
    │       │   ├── about.blade.php
    │       │   └── contact.blade.php
    │       └── sections/
    │           ├── hero.blade.php
    │           └── upcoming-events.blade.php
    └── Routes/
        └── web-routes.php
```

*Note: No `Contracts/`, `Models/`, `Repositories/`, or `Database/Migrations/` are present because Website owns zero persistence and zero business models.*

---

## 19. Generic Website vs Site-Specific Website Decision

- **Option 1 (One Generic Package `Webkul/Website`)**: High risk of creating a bloated, pseudo-CMS with endless configuration switches to satisfy multiple diverse site profiles.
- **Option 2 (Site-Specific Packages e.g. `CampusWebsite`, `AlumniWebsite`)**: Cleanest isolation and explicit dependency declaration per site profile.
- **Option 3 (Generic Website Foundation + Site Package)**: Redundant. `Webkul\Web` is ALREADY the generic website foundation.
- **Decision**:
  - `Webkul\Web` is the permanent generic public-web foundation.
  - `Webkul\Website` is the canonical reference site-composition package for the campus portal seed. If another entity develops a distinct website, they author their own site composition package using `Web` contracts.

---

## 20. Current Architecture Conflicts

| Current State | Target State | Conflict Classification | Future Action Required |
|---|---|---|---|
| `Web` unconditionally owns route `/` (`web.home`) | Website composes or customizes homepage | **ALREADY_COMPATIBLE** | Website should compose sections onto `'home'` via `SectionRegistryContract` or override `web::home.index` via Theme. |
| `Event` owns public routes `/events` and `/events/{id}` | Event owns default public pages; Website composes event sections | **ALREADY_COMPATIBLE** | Event continues owning default public pages; Website embeds event cards/sections via read contracts. |
| `Event` depends on `Webkul\Web` | Optional package depends on Foundation | **ALREADY_COMPATIBLE** | Permitted by Rule 08 Section 2 (`Optional -> Foundation: ALLOWED`). |
| `Base` theme overrides Web views | Base theme provides default safe presentation | **ALREADY_COMPATIBLE** | Base theme remains default fallback in `config/themes.php`. |
| `EventRepository` lacks explicit Public Read Contract | Website consumes stable contracts | **MINOR_CONFLICT (`MISSING_CONTRACT`)** | In a future step, define `EventPublicReadContract` or explicit public API. |
| Leftover static translation keys in `Web/src/Resources/lang/*/app.php` | Web translations are strictly business-neutral | **MINOR_CONFLICT** | Harmless static residue; can be pruned during next authorized Web cleanup wave. |

- **Current Architecture Conflicts**: 2 (`MISSING_CONTRACT` in Event, static translation residue in Web).
- **Structural Conflicts**: **0**.

---

## 21. Existing Mechanisms To Preserve

The following systems are functionally sound and must remain intact:
1. `Webkul\Theme\View\ThemeViewFinder`: Robust namespace protection, hierarchical theme overrides.
2. `ThemeRegistry` & `ThemeResolver`: Cycle-checked inheritance chains and configuration fallback.
3. `SectionRegistryContract`: Clean, ordered, callable-driven page section composition.
4. `NavigationRegistryContract` & `NavigationLabelResolver`: Request-locale-safe public navigation.
5. `WebContext` & `ResolveWebLocale`: Request-scoped locale, direction, and active theme authority.
6. `SeoService` & `SeoMetadataContract`: Encapsulated SEO tag management.
7. `Web` UI Component Kernel: Headless, accessible Blade primitives (`<x-web::*>`).
8. `OptionalPackageComposition`: Deterministic, deployment-configured package activation.

---

## 22. Permanent Architecture Laws Proposal

The following laws are proposed for governing `Website` development:

- **WEB-01**: `Webkul\Web` MUST operate normally and provide a complete public website when `Website` is absent.
- **WEB-02**: `Website` is an `OPTIONAL_FEATURE` / composition package and MUST NOT be classified as Foundation.
- **WEB-03**: Removing or unregistering `Website` MUST immediately restore default Web and Base theme presentation without editing Foundation or Business source.
- **WEB-04**: `Webkul\Web` MUST NOT depend on or reference `Website`.
- **WEB-05**: Business Packages (`Event`, `Student`, `LostAndFound`) MUST NOT depend on or reference `Website`.
- **WEB-06**: `Website` MAY consume only the Business Packages declared in its Composer manifest.
- **WEB-07**: `Website` MUST NOT own business domain logic, database tables, migrations, or domain authorization.
- **WEB-08**: `Webkul\Theme` and `themes/base` MUST remain completely business-neutral.
- **WEB-09**: Absence of `Website` is valid; an enabled `Website` with missing declared dependencies MUST fail fast during composition loading.
- **WEB-10**: `Website` MUST NOT introduce a secondary package activation system or database feature flags.

---

## 23. Risks

1. **Monolith Creep**: The temptation to put site-specific database tables or business workflows into `Website`. *Mitigation*: Strictly enforce Law WEB-07 (no migrations or models in `Website`).
2. **Hidden Business Dependencies**: Using reflection or soft checks to conditionally call Business Packages without declaring them. *Mitigation*: Enforce Composer dependencies via `OptionalPackageComposition`.
3. **Route Collisions**: If Website attempts to re-register `/` instead of composing sections or using view overrides. *Mitigation*: Establish `SectionRegistryContract` as the canonical homepage composition mechanism.

---

## 24. Blockers

- **ZERO physical blockers**. The existing `Web`, `Theme`, and `OptionalPackageComposition` architectures are already completely compatible with the proposed `Website` package.

---

## 25. Recommended Next Step

**Recommended Step: Step 02 — Contract Formalization & Website Specification Design**.  
In Step 02, formulate the exact specifications for:
1. `EventPublicReadContract` (or business public read contract) in `Webkul\Event`.
2. The exact manifest and service provider blueprint for `Webkul\Website`.
3. The formal section and navigation contributions for the campus portal.

---
