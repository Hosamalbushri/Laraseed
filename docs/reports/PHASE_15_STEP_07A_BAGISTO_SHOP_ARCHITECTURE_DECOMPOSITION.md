# CAMPUSFIND — PHASE 15 STEP 07A REPORT
## Bagisto Shop Architecture Decomposition & Web / Presentation Blueprint

- **Date:** 2026-10-01
- **Phase:** 15 (Architecture Transition & Package Decomposition)
- **Step:** 07A (Bagisto Shop Architecture Decomposition & Web / Presentation Blueprint)
- **Status:** CERTIFIED COMPLETE
- **Role Authority:** Principal Laravel Package Architect, Bagisto Architecture Researcher, Blade/Vue Component Architect, Frontend Build Architect, Presentation Architecture Engineer, and Reusable Seed Architect

---

## 1. Executive Summary

Phase 15 Step 07A performs a forensic architectural decomposition of Bagisto's storefront package (`Webkul/Shop` v2.4.12) and establishes the authoritative blueprint for separating reusable Web UI infrastructure from replaceable Presentation packages in CampusFind.

This phase is **strictly analytical and design-oriented**. Zero production source files, Blade views, Vue runtimes, stylesheets, configurations, routes, database tables, or dependencies have been modified.

### Key Decomposition Findings:
1. **Bagisto Shop is a Hybrid Monolith**: Bagisto's `Webkul/Shop` bundles four distinct concerns into a single package:
   - *Generic UI Infrastructure*: Modals, drawers, dropdowns, accordions, form control-groups, tables, breadcrumbs, and basic tabs.
   - *Final Storefront Presentation*: Header chrome, footer layout, branding fonts (Poppins, DM Serif), store color tokens (`navyBlue`, `lightOrange`), marketing layouts, and promotional banners.
   - *E-Commerce Domain*: Products, categories, cart operations, one-page checkout, order history, customer accounts, reviews, and wishlist.
   - *Theme Engine Integration*: Active channel theme resolution, `@bagistoVite` theme asset loading, and runtime view cascading via `Webkul\Theme`.
2. **The Core Architectural Mandate**: CampusFind decouples this hybrid monolith into three strictly bounded layers:
   ```text
   Web (Reusable UI Infrastructure Kernel)
      ↑ consumed by
   Presentation Package (Replaceable Visual Experience, e.g. Website)
      ↑ consumes public contracts of
   Domain Packages (Business Logic & Data, e.g. Student, LostAndFound)
   ```
3. **Form Architecture Parity**: Both Bagisto Shop and CampusFind's own `Webkul/Admin` use the **`control-group`** vocabulary:
   `<x-*:form.control-group>` -> `.label`, `.control`, `.error`.
   CampusFind Web will adopt this exact structural hierarchy (`<x-web::form.control-group>`), guaranteeing 100% conceptual alignment across Admin and Web. However, Web completely eliminates Bagisto's heavy VeeValidate dependency in favor of native Laravel server validation, `$errors`, `old()`, and progressive enhancement.
4. **Layout Separation**: Bagisto's `<x-shop::layouts>` mixes HTML document infrastructure with storefront header/footer/brand presentation. CampusFind cleanly bifurcates layout responsibility: Web owns the generic HTML5 document base shell (`web::layouts.base`), while the Presentation package owns the final application master layout (`website::layouts.master`).
5. **Theme Engine Rejection**: All Bagisto Theme Engine concepts (`Theme` middleware, `ThemeRegistry`, `ThemeResolver`, `ThemeViewFinder`, `themes/shop/default/build`, `@bagistoVite`) are permanently rejected.
6. **Reconstruction Roadmap**: Implementation must proceed in structural phases: establishing Web and Presentation package directory boundaries (Step 07B), followed by form controls, overlays, and presentation consumption.

---

## 2. Audit Scope

- **Authoritative External Reference**: Official Bagisto 2.4 source tree located locally at `bagisto-2.4/packages/Webkul/Shop/`.
- **Internal Reference**: Current CampusFind repository (`packages/Webkul/Web/`, `packages/Webkul/Website/`, `packages/Webkul/Admin/`, `packages/Webkul/DataGrid/`, `docs/rules/*`, `docs/reports/PHASE_15_STEP_*`).
- **Inspection Methodology**: Physical code examination of Composer manifests, ServiceProviders, middleware, routes, controllers, Blade components, Vue entrypoints, Tailwind configurations, Vite pipelines, CSS sources, and test suites.

---

## 3. Architectural Laws

1. **`WEB_IS_FINAL_PRESENTATION_OWNER = NO`**: Web is a domain-neutral, theme-free public UI infrastructure kernel. It does not own the storefront, website branding, or final page layouts.
2. **`WEB_OWNS_WHAT__PRESENTATION_OWNS_HOW`**:
   - `Web` owns component contracts, Blade tags, props, slots, events, interaction state, keyboard operability, focus traps, Escape handling, ARIA semantics, generic document shells, and minimal neutral fallback styling (`web-fallback.css`).
   - `Presentation` owns final page layouts, header/footer chrome, branding, color tokens, typography, spacing, shadows, responsive composition, and domain presentation integrations.
3. **Strict Dependency Hierarchy**:
   ```text
   Presentation ───> Web
   Presentation ───> Domain Public Contracts (Allowed)
   Web ───> Presentation = 0 (FORBIDDEN)
   Web ───> Domain = 0 (FORBIDDEN)
   Domain ───> Presentation = 0 (FORBIDDEN)
   ```
4. **Theme Engine Prohibition**: There is NO Theme Engine. No `ThemeRegistry`, `ThemeResolver`, `ThemeViewFinder`, `APP_THEME`, or multi-theme cascading. Standard Laravel view namespaces (`web::`, `website::`, `admin::`) are authoritative.
5. **Progressive Enhancement**: Server-rendered HTML is primary. Client-side Vue 3 enhances stateful components (`Modal`, `Drawer`, `Dropdown`, `Accordion`, `Tabs`) without blocking basic page readability or navigation.

---

## 4. Bagisto Version Pin

The forensic research was executed directly against the local Bagisto source repository:

```text
BAGISTO_REPOSITORY=bagisto/bagisto
BAGISTO_VERSION=2.4.12
BAGISTO_BRANCH=v2.4.12-release
BAGISTO_COMMIT=unpacked-release-archive
AUDIT_DATE=2026-10-01
AUTHORITATIVE_SOURCE_PATH=bagisto-2.4/packages/Webkul/Shop/
```

Verified via `bagisto-2.4/packages/Webkul/Core/src/Core.php` (`const BAGISTO_VERSION = '2.4.12';`).

---

## 5. CampusHub Baseline

Measured immediately prior to Step 07A:

| Metric | CampusHub Current Value | Invariant Status |
|---|---:|---|
| **PHP Tests Passing** | 569 tests | CLEAN |
| **Test Assertions** | 4,057 assertions | CLEAN |
| **Active Routes** | 105 routes | CLEAN |
| **Web Component Families** | 9 families | CLEAN |
| **Web Component Files** | 14 Blade files | CLEAN |
| **Vue createApp Count** | 1 (`mountPublicWebApp`) | CLEAN |
| **Vue Mount Target** | `#app` | CLEAN |
| **Compiled JS Size** | 192.14 kB (69.18 kB gzip) | CLEAN |
| **Compiled CSS Size** | 50.22 kB (10.16 kB gzip) | CLEAN |
| **Web -> Website References** | 0 references | CLEAN |
| **Web -> Student References** | 0 references | CLEAN |
| **Web -> LostAndFound References**| 0 references | CLEAN |
| **Theme Engine Present** | NO | CERTIFIED ABSENT |

---

## 6. Bagisto Shop Complete Package Tree

Physical tree of `bagisto-2.4/packages/Webkul/Shop/` (559 files total):

```text
bagisto-2.4/packages/Webkul/Shop/
├── .gitignore
├── composer.json (bagisto/laravel-shop)
├── package.json (devDependencies & npm dependencies)
├── postcss.config.cjs
├── tailwind.config.js
├── vite.config.js
├── src/
│   ├── CacheFilters/
│   │   └── ResponseCache.php
│   ├── Config/
│   │   └── menu.php
│   ├── Data/
│   │   └── Search/ (search criteria DTOs)
│   ├── DataGrids/
│   │   ├── DownloadableProductDataGrid.php
│   │   ├── OrderDataGrid.php
│   │   ├── ProductReviewDataGrid.php
│   │   └── RMADataGrid.php
│   ├── Helpers/
│   │   └── Price.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── CartController.php
│   │   │   ├── CMS/PageController.php
│   │   │   ├── Customer/ (Session, Registration, Account, Wishlist, Address)
│   │   │   ├── HomeController.php
│   │   │   ├── OnepageController.php
│   │   │   ├── ProductController.php
│   │   │   └── SearchController.php
│   │   ├── Middleware/
│   │   │   ├── AuthenticateCustomer.php
│   │   │   ├── CacheResponse.php
│   │   │   ├── Currency.php
│   │   │   ├── Locale.php
│   │   │   └── Theme.php (activates theme via ThemeRegistry)
│   │   ├── Requests/ (AddressRequest, CustomerRequest, ReviewRequest)
│   │   └── Resources/ (Cart, Category, Product API resources)
│   ├── Listeners/
│   │   ├── CatalogCache.php
│   │   ├── Customer.php
│   │   ├── EUWithdrawal/
│   │   ├── GDPR.php
│   │   ├── Invoice.php
│   │   ├── Order.php
│   │   ├── Refund.php
│   │   └── Shipment.php
│   ├── Mail/ (Customer, Order, GDPR, RMA notifications)
│   ├── Providers/
│   │   ├── EventServiceProvider.php
│   │   ├── ModuleServiceProvider.php
│   │   └── ShopServiceProvider.php
│   ├── Resources/
│   │   ├── assets/
│   │   │   ├── css/ (app.css)
│   │   │   ├── fonts/ (bagisto-shop.woff)
│   │   │   ├── images/
│   │   │   └── js/
│   │   │       ├── app.js
│   │   │       ├── directives/debounce.js
│   │   │       └── plugins/ (axios, emitter, flatpickr, shop, vee-validate)
│   │   ├── lang/ (24 localized translation directories)
│   │   ├── manifest.php
│   │   └── views/
│   │       ├── categories/
│   │       ├── checkout/
│   │       ├── cms/
│   │       ├── compare/
│   │       ├── components/ (95 Blade component templates)
│   │       ├── customers/
│   │       ├── emails/
│   │       ├── errors/
│   │       ├── eu-withdrawals/
│   │       ├── home/
│   │       ├── partials/ (breadcrumbs, pagination)
│   │       ├── products/
│   │       └── search/
│   ├── Routes/
│   │   ├── api.php
│   │   ├── checkout-routes.php
│   │   ├── customer-routes.php
│   │   ├── store-front-routes.php
│   │   ├── web.php
│   │   └── webmcp-routes.php
│   └── Traits/
└── tests/ (Playwright E2E and Feature tests)
```

---

## 7. Shop Responsibility Decomposition

Every architecturally significant area of Bagisto `Webkul/Shop` is decomposed by responsibility:

```text
                                  Bagisto Webkul/Shop (559 Files)
                                                 │
         ┌───────────────────┬───────────────────┴───────────────────┬───────────────────┐
         ▼                   ▼                                       ▼                   ▼
WEB_INFRASTRUCTURE      PRESENTATION                          COMMERCE_DOMAIN       THEME_ENGINE
 (Kernel Candidates)    (Website Candidates)                  (Domain / Separate)    (REJECT ENTIRELY)
         │                   │                                       │                   │
  - Blade Component   - Storefront Header/Footer              - Products / Pricing - Theme Middleware
    Contracts         - Branding & Design Tokens              - Cart / Checkout    - themes() helper
  - Modal, Drawer,    - Page Chrome & Master Layout           - Order Management   - ThemeViewFinder
    Dropdown, Tabs    - Homepage Sections                     - Customer Accounts  - @bagistoVite
  - Form Control-Group- Hero & Marketing Banners              - Wishlist / Compare - themes/shop/build
  - Base Document     - Responsive Composition                - GDPR / RMA         - theme.json manifests
    Shell (<base>)    - Final Component Appearance            - Sales Listeners
  - WebContext & SEO
```

---

## 8. Web Infrastructure Concerns

The following concerns from Bagisto Shop are classified as **`WEB_INFRASTRUCTURE`** and qualify for ownership by `Webkul\Web`:
1. **Component Contracts**: Blade component tags, props, slots, event definitions.
2. **Overlay Behaviors**: Modal dialogs, slideout drawers, popover dropdowns (focus trapping, focus return, Escape dismissal, backdrop click, scroll locking).
3. **Disclosure / Navigation Primitives**: Accordions, tabs (WAI-ARIA roving tabindex), breadcrumb trails, pagination shells.
4. **Form System Semantics**: `<form>` method spoofing, CSRF packaging, `control-group` wrappers, accessible `<label>` association, input type normalization, validation state (`aria-invalid`), error message presentation (`role="alert"`), helper text (`aria-describedby`).
5. **Document Shell Infrastructure**: Base HTML5 doctype, head metadata hooks (`@stack('meta')`, `@stack('styles')`), directionality resolution (`dir="rtl|ltr"`), skip-to-content accessibility link, Vue mount target (`#app`), deferred script stack (`@stack('scripts')`).
6. **Feedback Primitives**: Alert banners, flash message wrappers, loading spinners, skeleton placeholders.
7. **Table Presentation**: Semantic HTML `<table>`, `<thead>`, `<tbody>`, `<tr>`, `<th>`, `<td>` wrappers.
8. **Client Runtime Kernel**: Single Vue 3 instance, deterministic component registration, keyboard event managers, centralized overlay management.
9. **Minimal Fallback Presentation**: Neutral, self-contained CSS baseline (`web-fallback.css`) ensuring components function and render readably without a Presentation package.

---

## 9. Presentation Concerns

The following concerns from Bagisto Shop are classified as **`PRESENTATION`** and qualify for ownership by the Presentation Package (`Webkul\Website`):
1. **Master Page Layouts**: Final application layout templates (`website::layouts.master`).
2. **Site Chrome**: Brand header, logo rendering, search bar styling, primary navigation menus, footer widgets, copyright notices.
3. **Visual Design Tokens**: Brand color palettes (e.g. `navyBlue`, `amber`), typography hierarchies, font families (`Poppins`, `Cairo`, `Inter`), spacing scale, border radii, box shadows.
4. **Final Component Styling**: How buttons, cards, badges, inputs, and drawers visually look in the context of the website.
5. **Application Pages**: Homepage, about page, contact page, FAQ page, search results views.
6. **Homepage Sections**: Hero banners, feature grids, statistics displays, marketing sections.
7. **Domain Presentation Integrations**: Transforming domain DTOs (e.g. `LostItemDto`) into visual cards, galleries, and detail views.
8. **Asset Bundles**: `website.css`, site images, branding SVGs, favicons.

---

## 10. Commerce Domain Concerns

The following concerns from Bagisto Shop are classified as **`COMMERCE_DOMAIN`** and are **strictly excluded from Web**:
- Products, catalog categories, variant configurations, bookings, bundles.
- Pricing engines, currency formatting, exchange rate switchers.
- Shopping cart session management, mini-cart slideouts, shipping estimates, coupon codes.
- One-page checkout, billing/shipping address steps, payment gateway drivers (PayPal, Stripe, etc.).
- Wishlist, product comparison matrix, product star ratings, customer reviews.
- Customer authentication guards (`AuthenticateCustomer`), customer registration, profile edits.
- RMA (returns), GDPR data export PDFs, downloadable product tokens.
- Sales event listeners (`Order`, `Invoice`, `Shipment`, `Refund`, `CatalogCache`).

---

## 11. Theme Engine Concerns

The following concerns from Bagisto Shop are classified as **`THEME_ENGINE`** and are **permanently rejected**:
- `Webkul\Shop\Http\Middleware\Theme`: Active channel theme resolver.
- `themes()` helper and `ThemeRegistryContract` / `ThemeResolverContract`.
- `ThemeViewFinder`: Custom view finder cascade for view overrides.
- Multi-theme configuration (`config/themes.php`, `channel->theme`).
- `@bagistoVite` directive and dynamic theme build directories (`themes/shop/default/build`).
- `theme.json` manifests, parent/child inheritance chains.

---

## 12. Bagisto-Specific Concerns

The following concerns are unique to Bagisto's architecture and not needed by CampusFind:
- Full Page Cache (FPC) tags (`CacheFilters/ResponseCache.php`, `FPC::willCache()`).
- WebMCP tool registration for autonomous commerce agents (`<x-shop::layouts.webmcp>`).
- Flatpickr datepicker Vue plugin (`vue-flatpickr`).
- Bagisto custom icon webfont (`bagisto-shop.woff`, `.icon-*`).

---

## 13. Shop Bootstrap Architecture

In Bagisto, `Webkul\Shop\Providers\ShopServiceProvider` bootstraps the storefront:
1. `registerConfig()`: Merges customer menu config (`menu.customer`).
2. `boot()`:
   - Registers middleware group `'shop'` containing `[Theme::class, Locale::class, Currency::class]`.
   - Aliases middleware: `theme`, `locale`, `currency`, `cache.response`, `customer`.
   - Loads routes from `Routes/web.php` and `Routes/api.php` under `['web', 'shop', PreventRequestsDuringMaintenance::class]`.
   - Loads translations (`'shop'`), views (`'shop'`), migrations.
   - Registers default pagination views: `Paginator::defaultView('shop::partials.pagination')`.
   - Registers Blade component namespace: `Blade::anonymousComponentPath(__DIR__.'/../Resources/views/components', 'shop')`.
   - Boots `EventServiceProvider`.

### Architectural Critique:
Bagisto forces all storefront routes through the `Theme` middleware. In CampusFind, `Webkul\Web\Providers\WebServiceProvider` boots cleanly without theme middleware, registering only genuine web context (`ResolveWebLocale`), view namespace (`web`), and generic registries (`NavigationRegistry`, `SectionRegistry`).

---

## 14. Shop Providers

| Bagisto Provider | Responsibilities | Target Classification | CampusHub Owner |
|---|---|---|---|
| `ShopServiceProvider` | Route registration, view/lang loading, middleware, Blade component registration | MIXED (Infra + Presentation + Domain) | Decomposed: Web owns Blade/views; Presentation owns routes/pages |
| `EventServiceProvider`| 25+ listeners for catalog, orders, customers, GDPR, EU withdrawal | COMMERCE_DOMAIN | Domain packages (LostAndFound, Student) |
| `ModuleServiceProvider`| Concord module registration | BAGISTO_SPECIFIC | Not needed (standard Laravel) |

---

## 15. Shop Routes

Bagisto Shop routes are split across 6 files in `src/Routes/`:
- `web.php`: Master aggregator loading `store-front-routes.php`, `customer-routes.php`, `checkout-routes.php`.
- `store-front-routes.php`: Storefront pages (`/`, `categories/*`, `products/*`, `search`, `compare`, `contact-us`).
- `customer-routes.php`: Customer login, registration, account portal.
- `checkout-routes.php`: Cart, checkout, addresses, shipping, payment.
- `api.php`: Storefront REST endpoints.
- `webmcp-routes.php`: AI tool endpoints.

### Responsibility Decomposition:
- In Bagisto, Shop owns all public routes because Shop is the complete e-commerce storefront.
- In CampusFind:
  - `Webkul\Web` owns ONLY generic foundation routes (locale switch `web/locale/{code}`, fallback root route `/`).
  - Presentation (`Webkul\Website`) owns public site routes (`/`, `about`, `lost-found/*`).
  - Domain packages (`Student`, `LostAndFound`) own their respective workflow routes (`student/login`, `student/lost-found/*`).

---

## 16. Shop HTTP Layer

Decomposition of `bagisto-2.4/packages/Webkul/Shop/src/Http/`:

| Subdirectory | Responsibilities | Classification | Target CampusHub Destination |
|---|---|---|---|
| `Controllers/Customer/*` | Login, Register, Account, Orders, Wishlist | COMMERCE_DOMAIN | `Webkul\Student` (for student identity) |
| `Controllers/CartController` | Cart CRUD, coupons | COMMERCE_DOMAIN | Rejected (No commerce) |
| `Controllers/OnepageController` | Checkout checkout steps | COMMERCE_DOMAIN | Rejected (No commerce) |
| `Controllers/ProductController` | Product views, reviews, downloads | COMMERCE_DOMAIN | Rejected (No commerce) |
| `Controllers/HomeController` | Storefront home page | PRESENTATION | `Webkul\Website\Http\Controllers\HomeController` |
| `Middleware/Theme.php` | Set active theme from channel | THEME_ENGINE | **REJECTED** |
| `Middleware/Locale.php` | Locale resolution | WEB_INFRASTRUCTURE | `Webkul\Web\Http\Middleware\ResolveWebLocale` |
| `Middleware/Currency.php` | Currency resolution | COMMERCE_DOMAIN | Rejected |
| `Middleware/AuthenticateCustomer`| Customer auth guard | COMMERCE_DOMAIN | `Webkul\Student` |

---

## 17. Shop Resources

Decomposition of `bagisto-2.4/packages/Webkul/Shop/src/Resources/`:
- `assets/css/app.css`: Tailwind base + components + utilities + iconfont definitions. -> Split into `web-fallback.css` (Web) and `website.css` (Presentation).
- `assets/fonts/bagisto-shop.woff`: Custom iconfont. -> Rejected (CampusFind uses inline SVGs with `aria-hidden="true"`).
- `assets/js/app.js`: Vue 3 bootstrap, VeeValidate plugins, emitter, lazy image observer. -> Decomposed into clean `web-interactions.js`.
- `lang/*`: 24 language catalogs. -> Generic app keys belong to `web::app.*`; site presentation keys belong to `website::app.*`.
- `views/components/*`: 95 Blade components. -> Audited and classified in Section 20.
- `views/layouts/*`: Storefront layouts. -> Decomposed into `web::layouts.base` and `website::layouts.master`.

---

## 18. Shop Blade Namespace

In Bagisto:
```php
Blade::anonymousComponentPath(__DIR__.'/../Resources/views/components', 'shop');
```
This registers `<x-shop::*>` directly against `Resources/views/components/`.

In CampusFind:
```php
$this->loadViewsFrom(__DIR__.'/../Resources/views', 'web');
```
Laravel automatically maps `<x-web::*>` to `packages/Webkul/Web/src/Resources/views/components/`.
To achieve exact parity and prevent path resolution ambiguity, Web should explicitly register:
```php
Blade::anonymousComponentPath(__DIR__.'/../Resources/views/components', 'web');
```

---

## 19. Shop Component Registration

Bagisto registers Blade components as anonymous components. However, for interactive components (`modal`, `drawer`, `dropdown`, `tabs`, `media`, `carousel`), Bagisto embeds an inline `<script type="module">` block at the bottom of the Blade file that calls `app.component('v-*', ...)`.

### CampusHub Contrast:
- **Bagisto**: Scattered inline `<script type="module">` tags across 15+ Blade templates via `@pushOnce('scripts')`.
- **CampusHub**: Centralized, pre-compiled JavaScript bundle (`web-interactions.js`) registered deterministically in `registerPublicWebComponents(app)`.
- **Decision**: Reject Bagisto's scattered inline script registration. Preserve CampusHub's deterministic compilation.

---

## 20. Complete Shop Component Inventory

Every component family in `bagisto-2.4/packages/Webkul/Shop/src/Resources/views/components/` (23 families, 95 Blade files):

| Component Family | Files | Blade Tag | Backing Tech | Responsibility | Target Owner | Target Action |
|---|---:|---|---|---|---|---|
| **accordion** | 1 | `<x-shop::accordion>` | Vue (`v-accordion`) via inline script | WEB_INFRASTRUCTURE | `Webkul\Web` | ADAPT (Deterministic Vue) |
| **breadcrumbs** | 1 | `<x-shop::breadcrumbs>` | Blade + Diglactic Breadcrumbs | WEB_INFRASTRUCTURE | `Webkul\Web` | ADAPT (Native Blade) |
| **button** | 1 | `<x-shop::button>` | Vue (`v-button`) via inline script | WEB_INFRASTRUCTURE | `Webkul\Web` | ADAPT (Server Blade + Spinner) |
| **carousel** | 1 | `<x-shop::carousel>` | Vue (`v-carousel`) 399 lines | COMMERCE_DOMAIN | Rejected | **REJECT FROM WEB** |
| **categories** | 1 | `<x-shop::categories.carousel>` | Blade + Domain | COMMERCE_DOMAIN | Rejected | **REJECT FROM WEB** |
| **datagrid** | 7 | `<x-shop::datagrid.*>` | Blade + Vue table/toolbar | INFRASTRUCTURE | `Webkul\DataGrid` | PRESERVE IN DATAGRID |
| **drawer** | 1 | `<x-shop::drawer>` | Vue (`v-drawer`) via inline script | WEB_INFRASTRUCTURE | `Webkul\Web` | ADAPT (Harden ARIA/A11y) |
| **dropdown** | 2 | `<x-shop::dropdown>` | Vue (`v-dropdown`) via inline script | WEB_INFRASTRUCTURE | `Webkul\Web` | ADAPT (Fix menu role/A11y) |
| **flash-group** | 2 | `<x-shop::flash-group>` | Vue (`v-flash-group`) + emitter | WEB_INFRASTRUCTURE | `Webkul\Web` | ADAPT (Split Flash vs Toast) |
| **flat-picker** | 2 | `<x-shop::flat-picker.*>` | Flatpickr library | NOT_NEEDED | Rejected | **REJECT (Use Native Date)** |
| **form** | 5 | `<x-shop::form.*>` | VeeValidate (`v-form`, `v-field`) | WEB_INFRASTRUCTURE | `Webkul\Web` | ADAPT (Server-rendered parity) |
| **image-zoomer** | 1 | `<x-shop::image-zoomer>` | Vue product zoom | COMMERCE_DOMAIN | Rejected | **REJECT FROM WEB** |
| **layouts** | 8 | `<x-shop::layouts.*>` | Blade storefront layouts | PRESENTATION | `Webkul\Website` | SPLIT (Base in Web, Master in Website) |
| **media** | 2 | `<x-shop::media>` | Vue (`v-media`) file dropzone | WEB_INFRASTRUCTURE | `Webkul\Web` | DEFER (P2 primitive) |
| **modal** | 2 | `<x-shop::modal>` | Vue (`v-modal`) via inline script | WEB_INFRASTRUCTURE | `Webkul\Web` | ADAPT (Harden focus trap/A11y) |
| **products** | 3 | `<x-shop::products.*>` | Blade product cards/ratings | COMMERCE_DOMAIN | Rejected | **REJECT FROM WEB** |
| **quantity-changer**| 1| `<x-shop::quantity-changer>`| Vue stepper | COMMERCE_DOMAIN | Rejected | **REJECT FROM WEB** |
| **range-slider** | 1 | `<x-shop::range-slider>` | Vue price slider | COMMERCE_DOMAIN | Rejected | **REJECT FROM WEB** |
| **shimmer** | 51 | `<x-shop::shimmer.*>` | Blade animated pulse divs | WEB_INFRASTRUCTURE | `Webkul\Web` | ADAPT (Consolidate into `<skeleton>`) |
| **table** | 6 | `<x-shop::table.*>` | Pure HTML table wrappers | WEB_INFRASTRUCTURE | `Webkul\Web` | ADAPT (Semantic table wrapper) |
| **tabs** | 2 | `<x-shop::tabs>` | Vue (`v-tabs`) via inline script | WEB_INFRASTRUCTURE | `Webkul\Web` | ADAPT (WAI-ARIA compliance) |
| **tinymce** | 1 | `<x-shop::tinymce>` | TinyMCE editor wrapper | NOT_NEEDED | Rejected | **REJECT (Admin-only)** |

---

## 21. Shop Component Directory Architecture

Bagisto organizes components by functional family under `views/components/`:
```text
components/
├── accordion/index.blade.php
├── button/index.blade.php
├── drawer/index.blade.php
├── dropdown/index.blade.php, menu/item.blade.php
├── form/
│   ├── index.blade.php
│   └── control-group/
│       ├── index.blade.php
│       ├── label.blade.php
│       ├── control.blade.php
│       └── error.blade.php
├── modal/index.blade.php, confirm.blade.php
├── table/index.blade.php, thead.blade.php, tbody.blade.php, tr.blade.php, th.blade.php, td.blade.php
└── tabs/index.blade.php, item.blade.php
```

### Recommendation for Web:
Adopt this exact directory hierarchy in `packages/Webkul/Web/src/Resources/views/components/`. It is clean, nested, predictable, and established across the Bagisto ecosystem.

---

## 22. Component Composition Philosophy

Bagisto utilizes a cohesive subcomponent naming philosophy:
- Container: `<x-shop::form>`, `<x-shop::table>`, `<x-shop::modal>`
- Grouping: `<x-shop::form.control-group>`
- Parts: `.label`, `.control`, `.error`, `.header`, `.content`, `.footer`, `.item`
- Slots: `<x-slot:toggle>`, `<x-slot:header>`, `<x-slot:content>`, `<x-slot:footer>`

### CampusHub Adoption:
Web adopts this exact composition philosophy. No conflicting aliases (`group` vs `control-group`, `trigger` vs `toggle`).

---

## 23. Shop Form Architecture

A forensic trace of Bagisto's form system:
```blade
<x-shop::form action="{{ route('login') }}">
    <x-shop::form.control-group>
        <x-shop::form.control-group.label class="required">
            Email
        </x-shop::form.control-group.label>

        <x-shop::form.control-group.control
            type="email"
            name="email"
            rules="required|email"
            :value="old('email')"
            placeholder="email@example.com"
        />

        <x-shop::form.control-group.error control-name="email" />
    </x-shop::form.control-group>
</x-shop::form>
```

---

## 24. Shop Form Control Architecture

In Bagisto, `control.blade.php` is a 333-line polymorphic switch handling 14 input types (`text`, `email`, `password`, `select`, `multiselect`, `checkbox`, `radio`, `switch`, `textarea`, `date`, `datetime`, `file`, `color`, `custom`).
- Every input is wrapped in `<v-field>` from VeeValidate.
- Password inputs embed an inline Vue component (`v-password-visibility`) via `@pushOnce('scripts')`.
- Switches nest `<label>` inside `<label>` (violating HTML5 specifications).
- Radios and checkboxes rely on icon font classes on empty labels.

### CampusHub Blueprint:
- Adopt the `<x-web::form.control-group.control type="...">` polymorphic API for compatibility and ease of use.
- Implement each control type as a clean, self-contained subcomponent (`controls/text.blade.php`, `controls/select.blade.php`, `controls/textarea.blade.php`, `controls/checkbox.blade.php`, `controls/switch.blade.php`).
- Ensure full semantic HTML compliance (no nested labels, native `<select>`, accessible ARIA attributes).

---

## 25. VeeValidate Analysis

| Dimension | Bagisto Shop (VeeValidate) | CampusHub Web Target (Server-Driven) |
|---|---|---|
| **Validation Authority** | Client-side JavaScript schema (`rules="required|email"`) | Server-side Laravel `FormRequest` rules |
| **Error Binding** | Dynamic via `<v-error-message>` | Automatic via `$errors->first($name)` |
| **Old Value Binding** | Vue reactive state (`v-model` / `:value`) | Automatic via `old($name, $default)` |
| **Bundle Cost** | ~45 kB minified + 24 i18n JSON files | **0 kB** (Pure Blade server rendering) |
| **Progressive Enhancement** | Fails without JS or before JS mount | **100% Functional without JavaScript** |
| **Accessibility Linkage** | None (`v-error-message` lacks IDs) | Strict `aria-invalid` & `aria-describedby` |
| **Verdict** | **REJECT VEEVALIDATE** | **ADOPT STRUCTURAL PARITY WITHOUT VEEVALIDATE** |

---

## 26. Shop Layout Architecture

Bagisto's `<x-shop::layouts>` in `bagisto-2.4/packages/Webkul/Shop/src/Resources/views/components/layouts/index.blade.php` (181 lines) is a monolithic layout that renders the entire document shell, store header, cookie consent, services banner, and store footer.

---

## 27. Layout Responsibility Decomposition

| Layout Responsibility | Bagisto Implementation | Classification | Target Owner | Target Mechanism |
|---|---|---|---|---|
| HTML5 doctype & `<html>` | `<x-shop::layouts>` | WEB_INFRASTRUCTURE | `Webkul\Web` | `<x-web::layouts.base>` |
| Locale / Direction (`dir`) | `core()->getCurrentLocale()` | WEB_INFRASTRUCTURE | `Webkul\Web` | `$webContext->direction()` |
| Head metadata & title | `<x-shop::layouts>` | WEB_INFRASTRUCTURE | `Webkul\Web` | `@stack('meta')`, SeoService |
| Skip to main content link | `.skip-to-main-content-link` | WEB_INFRASTRUCTURE | `Webkul\Web` | `.web-skip-link` |
| Vue mount `#app` & script | Inline `<script> app.mount("#app")` | WEB_INFRASTRUCTURE | `Webkul\Web` | `mountPublicWebApp()` |
| Storefront Header | `<x-shop::layouts.header>` | PRESENTATION | `Webkul\Website` | `website::partials.header` |
| Storefront Footer | `<x-shop::layouts.footer>` | PRESENTATION | `Webkul\Website` | `website::partials.footer` |
| Google Fonts (Poppins) | `<link href="...Poppins...">` | PRESENTATION | `Webkul\Website` | `website::layouts.master` |
| Store Branding & Favicon | `core()->getCurrentChannel()` | PRESENTATION | `Webkul\Website` | `SiteDefinition` |
| Fallback Document Layout | None (Shop IS the presentation) | WEB_INFRASTRUCTURE | `Webkul\Web` | `web::layouts.master` (fallback) |

---

## 28. Shop Theme Dependencies

Bagisto Shop is tightly bound to `Webkul\Theme`:
- In `ShopServiceProvider.php`: loads `Theme::class` middleware on all storefront requests.
- In `Theme.php`: resolves channel theme via `themes()->set(...)`.
- In views: loads assets via `@bagistoVite(['...'])`.
- In `ThemeViewFinder`: dynamically intercepts view resolution to load theme overrides.

---

## 29. Rejected Theme Architecture

All 6 pillars of Bagisto's Theme Engine are permanently banned from CampusFind:
1. `ThemeRegistry` / `ThemeResolver` -> BANNED.
2. `ThemeViewFinder` view cascading -> BANNED.
3. `Theme` middleware -> BANNED.
4. `@bagistoVite` asset resolver -> BANNED.
5. Runtime theme selection / `APP_THEME` -> BANNED.
6. Parent/child theme inheritance -> BANNED.

Standard Laravel package view namespaces (`web::*`, `website::*`, `admin::*`) are authoritative and exclusive.

---

## 30. Shop Asset Ownership

In Bagisto:
- `Shop` owns its own `package.json`, `vite.config.js`, `tailwind.config.js`, and `postcss.config.cjs`.
- Assets compile into `public/themes/shop/default/build/`.
- Shop owns storefront CSS (`app.css`), iconfonts (`bagisto-shop.woff`), and JS (`app.js`).

---

## 31. Shop Frontend Build Architecture

Bagisto uses per-package independent Vite builds:
- Shop builds via `cd packages/Webkul/Shop && npm run build`.
- Admin builds via `cd packages/Webkul/Admin && npm run build`.
- This creates multi-build fragmentation, multiple manifests, and requires custom Blade directives (`@bagistoVite`) to resolve asset paths.

### CampusHub Rule 14 Invariant:
CampusFind strictly maintains a **Single Root Build Pipeline** (`npm run build` at root), emitting assets to `public/build/manifest.json`. We will not revert to multi-build fragmentation.

---

## 32. Shop Tailwind Architecture

Bagisto Shop's `tailwind.config.js`:
- Scans `./src/Resources/**/*.blade.php` and `./src/Resources/**/*.js`.
- Injects store colors (`navyBlue`, `lightOrange`, `darkGreen`, `darkBlue`, `darkPink`).
- Injects store fonts (`Poppins`, `DM Serif Display`).
- Safelists `/icon-/` regex for font icons.

---

## 33. Shop CSS Architecture

Bagisto Shop's `app.css` mixes Tailwind directives with 400+ lines of `.icon-*` font-glyph definitions and inline utility classes. There is zero separation between component behavioral baseline CSS and storefront theme CSS.

### CampusHub Separation:
- **`packages/Webkul/Web/`**: Owns `web-fallback.css` (neutral, accessible, semantic `.web-*` classes, CSS custom properties `--web-fallback-*`, logical properties).
- **`packages/Webkul/Website/`**: Owns `website.css` (Tailwind design tokens, site-specific classes, branding).

---

## 34. Shop Responsive Architecture

Bagisto Shop defines custom screens (`sm: 525px`, `md: 768px`, `lg: 1024px`, `xl: 1240px`, `1180px`, `1060px`, `991px`, `868px`).
- Breakpoints are hardcoded into Tailwind.
- Responsive design is handled primarily at the template level via utility classes (`max-md:hidden`, `max-sm:px-4`).

---

## 35. Shop RTL Architecture

Bagisto Shop handles RTL via:
- Tailwind `rtl:` and `ltr:` variant prefixes (`ltr:right-0 rtl:left-0`, `ltr:pr-12 rtl:pl-12`).
- Direction is resolved via `core()->getCurrentLocale()->direction`.

### CampusHub Target:
- Derives direction strictly from `WebContextContract` (`$webContext->direction()`, `$webContext->isRtl()`).
- Combines CSS logical properties (`margin-inline`, `padding-inline`, `inset-inline-start`) in `web-fallback.css` with bidirectional Tailwind utility variants in presentation templates.

---

## 36. Shop JavaScript Architecture

Bagisto Shop JS structure:
```text
src/Resources/assets/js/
├── app.js
├── directives/debounce.js
└── plugins/
    ├── axios.js
    ├── emitter.js (mitt)
    ├── flatpickr.js
    ├── shop.js
    └── vee-validate.js
```

---

## 37. Shop Vue Architecture

- `createApp` count = 1.
- In-browser compiler enabled (`vue.esm-bundler`).
- Root application mounts over `<div id="app">`.
- Components are registered on `window.app` via scattered `@pushOnce('scripts')` `<script type="module">` tags.

---

## 38. Blade/Vue Boundary

- Blade renders the initial HTML structure and supplies server data to slots.
- Vue mounts over custom tags (e.g. `<v-modal>`, `<v-drawer>`, `<v-dropdown>`).
- State is owned by Vue; markup structure is owned by Blade.
- In CampusFind, this boundary is strictly codified: consumer developers use `<x-web::modal>`, while `<v-web-modal>` remains an encapsulated internal implementation detail.

---

## 39. Inline Template Analysis

Bagisto's use of `<script type="text/x-template">` and `<script type="module">` inside Blade templates is **REJECTED**:
- Violates Content Security Policy (CSP).
- Fragments compilation (bypasses Vite optimization and tree-shaking).
- Bloats wire payload.
- CampusHub compiles 100% of Vue interaction code through Vite into `web-interactions.js`.

---

## 40. Shop Performance Architecture

Performance hazards identified in Bagisto Shop:
1. Synchronous layout thrashing in Dropdown (`clientWidth` and `clientHeight` queried on toggle).
2. Per-instance `window.addEventListener('click')` listeners without centralized delegation.
3. VeeValidate schema overhead on simple forms.
4. Large in-browser template compilation payload.

CampusHub avoids these by using native inputs, zero layout thrashing, and planned centralized overlay listener delegation.

---

## 41. Generic Component Candidates

Accepted for the future Web Component Kernel:
- Actions: `button`
- Surfaces: `card` (`header`, `content`, `footer`)
- Forms: `form`, `form.control-group`, `form.control-group.label`, `form.control-group.control`, `form.control-group.error`, `form.control-group.hint`
- Form Controls: `input`, `select` (native), `textarea`, `checkbox`, `radio`, `switch`
- Feedback: `alert`, `flash`, `spinner`, `skeleton`, `badge`
- Overlays: `modal`, `drawer`, `dropdown`
- Disclosure / Navigation: `accordion`, `tabs`, `breadcrumbs`, `pagination`
- Data / Media: `table`, `image`, `empty-state`
- Structural: `container`, `section`

---

## 42. Presentation Components

Components belonging to Presentation (`Website`):
- `website::layouts.master`
- `website::partials.header`
- `website::partials.footer`
- `website::sections.hero`
- `website::sections.features`
- `website::sections.announcements`
- `website::sections.lost-found`
- `website::lost-found.card`
- `website::lost-found.gallery`

---

## 43. Rejected Commerce Components

Permanently rejected from generic Web:
- `products.card`, `products.carousel`, `products.ratings`, `cart.*`, `checkout.*`, `mini-cart`, `quantity-changer`, `range-slider`, `carousel`, `image-zoomer`.

---

## 44. Public API Parity

| Bagisto Shop Tag | Proposed CampusHub Web Tag | Status |
|---|---|---|
| `<x-shop::button>` | `<x-web::button>` | PARITY |
| `<x-shop::form>` | `<x-web::form>` | PARITY |
| `<x-shop::form.control-group>` | `<x-web::form.control-group>` | PARITY |
| `<x-shop::form.control-group.label>` | `<x-web::form.control-group.label>` | PARITY |
| `<x-shop::form.control-group.control>` | `<x-web::form.control-group.control>` | PARITY |
| `<x-shop::form.control-group.error>` | `<x-web::form.control-group.error>` | PARITY |
| `<x-shop::modal>` | `<x-web::modal>` | PARITY (Hardened A11y) |
| `<x-shop::drawer>` | `<x-web::drawer>` | PARITY (Hardened A11y) |
| `<x-shop::dropdown>` | `<x-web::dropdown>` | PARITY (Hardened A11y) |
| `<x-shop::accordion>` | `<x-web::accordion>` | PARITY |
| `<x-shop::tabs>` | `<x-web::tabs>` | PARITY (WAI-ARIA) |
| `<x-shop::table>` | `<x-web::table>` | PARITY |
| `<x-shop::breadcrumbs>` | `<x-web::breadcrumbs>` | PARITY |
| `<x-shop::flash-group>` | `<x-web::flash>` | ADAPTED |
| `<x-shop::shimmer.*>` | `<x-web::skeleton>` | ADAPTED |
| `<x-shop::layouts>` | `<x-web::layouts.base>` + `<x-website::layouts>` | SPLIT |

---

## 45. Directory Parity Table

| Bagisto Shop Path | Responsibility | Current CampusHub Path | Target Owner | Target Path | Action |
|---|---|---|---|---|---|
| `src/Resources/views/components/form/` | Form controls | `packages/Webkul/Web/src/Resources/views/components/form/` | `Webkul\Web` | `packages/Webkul/Web/src/Resources/views/components/form/` | RESTRUCTURE to `control-group` |
| `src/Resources/views/components/modal/` | Modal dialog | `packages/Webkul/Web/src/Resources/views/components/modal.blade.php` | `Webkul\Web` | `packages/Webkul/Web/src/Resources/views/components/modal/index.blade.php` | RESTRUCTURE to folder |
| `src/Resources/views/components/drawer/` | Drawer slideout | `packages/Webkul/Web/src/Resources/views/components/drawer.blade.php` | `Webkul\Web` | `packages/Webkul/Web/src/Resources/views/components/drawer/index.blade.php` | RESTRUCTURE to folder |
| `src/Resources/views/components/dropdown/`| Dropdown popover | `packages/Webkul/Web/src/Resources/views/components/dropdown.blade.php` | `Webkul\Web` | `packages/Webkul/Web/src/Resources/views/components/dropdown/index.blade.php` | RESTRUCTURE to folder |
| `src/Resources/views/components/tabs/` | Tabbed panels | None | `Webkul\Web` | `packages/Webkul/Web/src/Resources/views/components/tabs/` | CREATE |
| `src/Resources/views/components/table/` | Semantic table | None | `Webkul\Web` | `packages/Webkul/Web/src/Resources/views/components/table/` | CREATE |
| `src/Resources/views/components/layouts/` | Storefront layout | `packages/Webkul/Website/src/Resources/views/layouts/` | SPLIT | Base in Web; Master in Website | SPLIT |
| `src/Resources/assets/css/` | CSS source | `packages/Webkul/Web/src/Resources/assets/css/` | `Webkul\Web` | `packages/Webkul/Web/src/Resources/assets/css/` | KEEP & HARDEN fallback |
| `src/Resources/assets/js/` | JS source | `packages/Webkul/Web/src/Resources/assets/js/` | `Webkul\Web` | `packages/Webkul/Web/src/Resources/assets/js/` | RESTRUCTURE & modularize |

---

## 46. Provider Parity Table

| Shop Provider Concern | Bagisto Shop Owner | CampusHub Current Owner | Target Owner | Target Decision |
|---|---|---|---|---|
| View & translation loading | `ShopServiceProvider` | `WebServiceProvider` | `Webkul\Web` | KEEP |
| Blade component namespace | `ShopServiceProvider` (`Blade::anonymousComponentPath`) | `WebServiceProvider` (`loadViewsFrom`) | `Webkul\Web` | EXPLICIT `Blade::anonymousComponentPath` |
| Web context & locale | `Locale` middleware | `WebServiceProvider` (`scoped WebContext`) | `Webkul\Web` | KEEP |
| Public navigation registry | None (hardcoded menu) | `WebServiceProvider` (`NavigationRegistry`) | `Webkul\Web` | KEEP |
| Composable section registry | None (CMS blocks) | `WebServiceProvider` (`SectionRegistry`) | `Webkul\Web` | KEEP |
| SEO metadata service | None (core helper) | `WebServiceProvider` (`SeoService`) | `Webkul\Web` | KEEP |
| Active theme resolution | `Theme` middleware | Obsolete / Deleted in Step 05 | NONE | **REJECTED** |
| Customer auth middleware | `AuthenticateCustomer` | `Webkul\Student` | `Webkul\Student` | PRESERVE IN DOMAIN |

---

## 47. Asset Parity Table

| Concern | Bagisto Shop | Current Web | Current Website | Target Owner | Target Architecture |
|---|---|---|---|---|---|
| **CSS** | `app.css` (Tailwind + fonts) | `web-fallback.css` | `website.css` | Web / Website | Web owns fallback; Website owns Tailwind tokens |
| **JS** | `app.js` (Vue + VeeValidate) | `web-interactions.js` | None | `Webkul\Web` | Web owns compiled interaction runtime |
| **Vue** | In-browser compiler + inline scripts | In-browser compiler (`vue.esm-bundler`)| None | `Webkul\Web` | Single `#app` mount, Vite pre-compiled |
| **Vite** | Per-package config (`themes/shop/default`) | Root `vite.config.js` | Root `vite.config.js` | Root Application | Single root build pipeline (Rule 14) |
| **Tailwind** | Package-local `tailwind.config.js` | Root `tailwind.config.js` | Root `tailwind.config.js` | Root Application | Root scans Web and Website |
| **PostCSS** | Package-local `postcss.config.cjs` | Root `postcss.config.js` | Root `postcss.config.js` | Root Application | Root PostCSS |
| **Images** | `src/Resources/assets/images/` | None | `Website/src/Resources/assets/` | `Webkul\Website` | Presentation owns branding images |
| **Fonts** | `bagisto-shop.woff` iconfont | System fonts fallback | System fonts + Cairo/Inter | `Webkul\Website` | Presentation owns brand fonts |

---

## 48. Component Parity Table

Comprehensive mapping of all 22 target P0/P1 components against Bagisto Shop equivalents:

| Component | Bagisto Analogue | Generic? | JS Tech | A11y Complexity | Priority | Decision | Target Tag |
|---|---|---|---|---|---|---|---|
| Button | `<x-shop::button>` | YES | Static (Blade) | Low | P0 | KEEP_BUT_HARDEN | `<x-web::button>` |
| Card | None (inline divs) | YES | Static (Blade) | Low | P0 | KEEP_AS_IS | `<x-web::card>` |
| Badge | None (inline spans)| YES | Static (Blade) | Low | P0 | KEEP_BUT_HARDEN | `<x-web::badge>` |
| Alert | None (flash only) | YES | Delegated JS | Medium | P0 | KEEP_BUT_HARDEN | `<x-web::alert>` |
| Form | `<x-shop::form>` | YES | Static (Blade) | Medium | P0 | CREATE | `<x-web::form>` |
| Control Group | `<x-shop::form.control-group>`| YES | Static (Blade) | Medium | P0 | CREATE | `<x-web::form.control-group>` |
| Control Label | `<x-shop::form.control-group.label>`| YES | Static (Blade) | Medium | P0 | CREATE | `<x-web::form.control-group.label>` |
| Control Input | `<x-shop::form.control-group.control>`| YES | Static (Blade) | High | P0 | CREATE | `<x-web::form.control-group.control>` |
| Control Error | `<x-shop::form.control-group.error>`| YES | Static (Blade) | High | P0 | CREATE | `<x-web::form.control-group.error>` |
| Control Hint | None | YES | Static (Blade) | Medium | P0 | CREATE | `<x-web::form.control-group.hint>` |
| Spinner | Embedded in button | YES | SVG (Blade) | Low | P0 | CREATE | `<x-web::spinner>` |
| Pagination | `shop::partials.pagination`| YES | Static (Blade) | Medium | P0 | CREATE | `<x-web::pagination>` |
| Accordion | `<x-shop::accordion>` | YES | Vue (`WebAccordion`)| High | P0 | KEEP_AS_IS | `<x-web::accordion>` |
| Modal | `<x-shop::modal>` | YES | Vue (`WebModal`) | Very High | P0 | KEEP_BUT_HARDEN | `<x-web::modal>` |
| Drawer | `<x-shop::drawer>` | YES | Vue (`WebDrawer`) | Very High | P0 | KEEP_BUT_HARDEN | `<x-web::drawer>` |
| Dropdown | `<x-shop::dropdown>` | YES | Vue (`WebDropdown`)| Very High | P0 | KEEP_BUT_HARDEN | `<x-web::dropdown>` |
| Tabs | `<x-shop::tabs>` | YES | Vue (`WebTabs`) | Very High | P1 | CREATE | `<x-web::tabs>` |
| Breadcrumbs | `<x-shop::breadcrumbs>`| YES | Static (Blade) | Medium | P1 | CREATE | `<x-web::breadcrumbs>` |
| Empty State | None (inline divs) | YES | Static (Blade) | Low | P1 | CREATE | `<x-web::empty-state>` |
| Skeleton | `<x-shop::shimmer.*>` | YES | Static (Blade) | Low | P1 | CREATE | `<x-web::skeleton>` |
| Table | `<x-shop::table>` | YES | Static (Blade) | Medium | P1 | CREATE | `<x-web::table>` |
| Image | `<x-shop::media.images.lazy>`| YES| Static (Blade) | Medium | P1 | CREATE | `<x-web::image>` |

---

## 49. Form Parity Table

| Form Concept | Bagisto Shop API | Bagisto Behavior | Shop JS Dependency | Target Web API | Target Web Behavior | Presentation Responsibility |
|---|---|---|---|---|---|---|
| **Form** | `<x-shop::form>` | Renders `<v-form>`, injects CSRF/method | VeeValidate | `<x-web::form>` | Renders `<form>`, auto-CSRF, `@method` spoofing, auto-enctype | Border/padding/spacing |
| **Control Group**| `<x-shop::form.control-group>`| Renders container div | None | `<x-web::form.control-group>` | Renders semantic `.web-form-group` div | Spacing/margins |
| **Label** | `<x-shop::form.control-group.label>`| Renders `<label>` | None | `<x-web::form.control-group.label>` | Semantic `<label for="...">`, auto-required asterisk | Font, color, weight |
| **Text Input** | `<x-shop::form.control-group.control type="text">`| Renders `<v-field><input>` | VeeValidate | `<x-web::form.control-group.control type="text">` | Native `<input>`, auto-`old()`, auto-`aria-invalid` | Border, radius, focus ring |
| **Select** | `<x-shop::form.control-group.control type="select">`| Renders `<v-field><select>` | VeeValidate | `<x-web::form.control-group.control type="select">` | Native `<select>`, auto-`selected`, placeholder option | Background, arrow icon, border |
| **Textarea** | `<x-shop::form.control-group.control type="textarea">`| Renders `<v-field><textarea>` | VeeValidate | `<x-web::form.control-group.control type="textarea">` | Native `<textarea>`, auto-`old()`, configurable rows | Border, resize, padding |
| **Checkbox** | `<x-shop::form.control-group.control type="checkbox">`| Renders iconfont on label | VeeValidate | `<x-web::form.control-group.control type="checkbox">` | Accessible `<input type="checkbox">` with label | Checkbox accent color |
| **Radio** | `<x-shop::form.control-group.control type="radio">`| Renders iconfont on label | VeeValidate | `<x-web::form.control-group.control type="radio">` | Accessible `<input type="radio">` with group name | Radio accent color |
| **Switch** | `<x-shop::form.control-group.control type="switch">`| Nested `<label>` tags | VeeValidate | `<x-web::form.control-group.control type="switch">` | Semantic `<input type="checkbox" role="switch">` | Toggle pill track & thumb |
| **Error** | `<x-shop::form.control-group.error>`| Renders `<v-error-message>` | VeeValidate | `<x-web::form.control-group.error>` | Renders `role="alert"`, checks `$errors->first($name)` | Text color, font size |
| **Hint** | None | None | None | `<x-web::form.control-group.hint>` | Renders `<p id="$id-hint">`, linked via `describedBy` | Muted text color |

---

## 50. Layout Parity Table

| Concern | Bagisto Shop Layout | Current Web Layout | Current Website Layout | Target Web Base | Target Presentation Master |
|---|---|---|---|---|---|
| Doctype & `<html>` | `<x-shop::layouts>` | `web::layouts.base` | `website::layouts.master` | `<x-web::layouts.base>` | Extends Web Base |
| `<head>` meta & title | `<x-shop::layouts>` | `web::layouts.base` | `website::layouts.master` | `<x-web::layouts.base>` | Supplements page title |
| Locale & Direction | `core()->getCurrentLocale()`| `WebContextContract`| `WebContextContract` | `WebContextContract` | Consumes WebContext |
| Skip Link | Embedded in layout | `web-fallback.css` | `website::layouts.master` | Standard `.web-skip-link` | Preserves skip target |
| Vue `#app` Mount | Embedded in layout | `web::layouts.base` | `website::layouts.master` | Standard `<div id="app">` | Wraps layout content |
| Header Chrome | Embedded in layout | Minimal fallback | `website::partials.header` | Fallback brand link | Full site header & nav |
| Footer Chrome | Embedded in layout | Minimal fallback | `website::partials.footer` | Fallback copyright | Full site footer |
| Branding & Tokens | Hardcoded Tailwind | `web-fallback.css` | `website.css` | Fallback tokens only | Full brand tokens |

---

## 51. Behavior vs Presentation Matrix

| Component Concern | Web Kernel Authority | Presentation Package Authority |
|---|---:|---:|
| **Component Tag & Public Props** | **YES** | NO |
| **Named Slots (`trigger`, `header`, etc.)**| **YES** | NO |
| **Interaction State (`open`, `active`)** | **YES** | NO |
| **Keyboard Event Handlers** | **YES** | NO |
| **Focus Trapping & Focus Return** | **YES** | NO |
| **Scroll Lock Management** | **YES** | NO |
| **WAI-ARIA Attributes & Roles** | **YES** | NO |
| **Form Error & Old Value Resolution**| **YES** | NO |
| **Generic Structural CSS / Layout** | **YES** | NO |
| **Neutral Fallback Styles** | **YES** | NO |
| **Brand Color Tokens** | NO | **YES** |
| **Typography & Font Families** | NO | **YES** |
| **Border Radius & Shadow Elevation** | NO | **YES** |
| **Page Layout & Chrome (Header/Footer)**| NO | **YES** |
| **Responsive Page Composition** | NO | **YES** |
| **Domain DTO Presentation** | NO | **YES** |

---

## 52. Current Web Gap Analysis

1. **Missing Form Primitives**: No `<form>`, no `control-group`, no native `select`, no `textarea`, no `checkbox`, no `radio`, no `switch`.
2. **Missing Feedback Primitives**: No `<spinner>`, no `<skeleton>`, no `<flash>`.
3. **Missing Navigation Primitives**: No `<pagination>`, no `<tabs>`, no `<breadcrumbs>`.
4. **Missing Data Primitives**: No `<table.*>`, no `<empty-state>`.
5. **Component Organization**: Currently flat (all in `views/components/*.blade.php`) rather than grouped into nested component folders (`modal/`, `drawer/`, `dropdown/`, `form/control-group/`).
6. **Overlay Styling Defect**: Overlays currently have hardcoded Tailwind utility classes rather than neutral BEM fallback styles in `web-fallback.css`.
7. **Listener Scaling**: `WebModal`, `WebDrawer`, and `WebDropdown` attach per-instance `document.addEventListener('click')` handlers.

---

## 53. Current Website Gap Analysis

1. **Raw HTML Duplication**: `lost-found/index.blade.php` duplicates raw `<form>`, `<input>`, `<select>`, `<button>`, badges, cards, and 28 lines of custom pagination HTML.
2. **Hardcoded Directional Arrows**: Website pagination hardcodes `← Previous` and `Next →`, which point backwards in Arabic RTL.
3. **Single Web Consumer**: Website currently consumes only 1 Web component (`<x-web::drawer>`) because the required form and pagination primitives do not exist in Web.

---

## 54. Web/Presentation Boundary Problems

1. **Root Build Coupling (`ROOT_BUILD_KNOWS_WEBSITE`)**: Root `vite.config.js` and `tailwind.config.js` hardcode explicit paths to `packages/Webkul/Website/`.
2. **Presentation Leaking into Kernel**: Overlays in Web contain specific Tailwind styling choices (e.g. `bg-white rounded-2xl border border-slate-200 shadow-2xl`) that belong to presentation rather than fallback baseline.

---

## 55. Target Web Architecture

`Webkul\Web` is organized as a clean, domain-neutral UI infrastructure kernel:
- **`src/Contracts/`**: `WebContextContract`, `NavigationRegistryContract`, `SectionRegistryContract`, `SeoMetadataContract`.
- **`src/Context/`**: `WebContext` (pure locale and direction authority).
- **`src/Http/`**: `Controllers/LocaleController`, `Controllers/HomeController` (fallback root), `Middleware/ResolveWebLocale`.
- **`src/Navigation/`**: `NavigationRegistry`, `NavigationItem`, `NavigationLabelResolver`.
- **`src/Sections/`**: `SectionRegistry`, `SectionDefinition`.
- **`src/Seo/`**: `SeoService`.
- **`src/Resources/assets/`**:
  - `css/web-fallback.css`: Minimal, self-contained, accessible CSS baseline for all primitives.
  - `js/web-interactions.js`: Compiled Vue 3 interaction runtime.
- **`src/Resources/views/`**:
  - `components/`: Pure Blade component library (`<x-web::*>`).
  - `layouts/base.blade.php`: Base HTML5 document shell.
  - `layouts/master.blade.php`: Fallback presentation master layout.
  - `home/index.blade.php`: Fallback root page rendering composable sections.

---

## 56. Target Presentation Architecture

The Presentation Package (`Webkul\Website`) is organized as the application's visual experience layer:
- **`src/SiteDefinition/`**: `SiteDefinitionContract`, `SiteDefinition` (brand authority, logos, contact info, SEO defaults).
- **`src/Providers/WebsiteServiceProvider.php`**: Registers sections, navigation items, view namespace (`website`), and routes.
- **`src/Http/Controllers/`**: `HomeController`, `AboutController`, `Integrations/LostAndFoundController`.
- **`src/Resources/assets/`**:
  - `css/website.css`: Application Tailwind stylesheet, design tokens, and `.web-*` styling overrides.
  - `images/`: Brand logos, favicons, site graphics.
- **`src/Resources/views/`**:
  - `layouts/master.blade.php`: Site master layout extending `web::layouts.base`.
  - `partials/header.blade.php`: Site header, brand logo, desktop navigation, mobile drawer trigger.
  - `partials/footer.blade.php`: Site footer, copyright, contact info.
  - `sections/`: Site-specific home sections (hero, features, announcements, lost-found).
  - `pages/`: Site pages (`about.blade.php`).
  - `lost-found/`: Public Lost & Found directory search and item detail views (consuming Web components).

---

## 57. Target Web Package Tree

Proposed final directory tree for `packages/Webkul/Web/`:

```text
packages/Webkul/Web/
├── composer.json
├── src/
│   ├── Context/
│   │   └── WebContext.php
│   ├── Contracts/
│   │   ├── NavigationRegistryContract.php
│   │   ├── SectionRegistryContract.php
│   │   ├── SeoMetadataContract.php
│   │   └── WebContextContract.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── HomeController.php
│   │   │   └── LocaleController.php
│   │   └── Middleware/
│   │       └── ResolveWebLocale.php
│   ├── Navigation/
│   │   ├── NavigationItem.php
│   │   ├── NavigationLabel.php
│   │   ├── NavigationLabelResolver.php
│   │   └── NavigationRegistry.php
│   ├── Providers/
│   │   └── WebServiceProvider.php
│   ├── Resources/
│   │   ├── assets/
│   │   │   ├── css/
│   │   │   │   └── web-fallback.css
│   │   │   └── js/
│   │   │       ├── web-interactions.js
│   │   │       └── vue/
│   │   │           ├── app.js
│   │   │           ├── components.js
│   │   │           └── components/
│   │   │               ├── WebAccordion.js
│   │   │               ├── WebDrawer.js
│   │   │               ├── WebDropdown.js
│   │   │               ├── WebModal.js
│   │   │               └── WebTabs.js
│   │   ├── lang/
│   │   │   ├── ar/app.php
│   │   │   └── en/app.php
│   │   └── views/
│   │       ├── components/
│   │       │   ├── accordion/
│   │       │   │   ├── index.blade.php
│   │       │   │   └── item.blade.php
│   │       │   ├── alert/
│   │       │   │   └── index.blade.php
│   │       │   ├── badge/
│   │       │   │   └── index.blade.php
│   │       │   ├── breadcrumbs/
│   │       │   │   └── index.blade.php
│   │       │   ├── button/
│   │       │   │   └── index.blade.php
│   │       │   ├── card/
│   │       │   │   ├── content.blade.php
│   │       │   │   ├── footer.blade.php
│   │       │   │   ├── header.blade.php
│   │       │   │   └── index.blade.php
│   │       │   ├── container/
│   │       │   │   └── index.blade.php
│   │       │   ├── drawer/
│   │       │   │   └── index.blade.php
│   │       │   ├── dropdown/
│   │       │   │   ├── index.blade.php
│   │       │   │   └── menu/item.blade.php
│   │       │   ├── empty-state/
│   │       │   │   └── index.blade.php
│   │       │   ├── flash/
│   │       │   │   └── index.blade.php
│   │       │   ├── form/
│   │       │   │   ├── index.blade.php
│   │       │   │   └── control-group/
│   │       │   │       ├── index.blade.php
│   │       │   │       ├── label.blade.php
│   │       │   │       ├── control.blade.php
│   │       │   │       ├── error.blade.php
│   │       │   │       ├── hint.blade.php
│   │       │   │       └── controls/
│   │       │   │           ├── checkbox.blade.php
│   │       │   │           ├── input.blade.php
│   │       │   │           ├── radio.blade.php
│   │       │   │           ├── select.blade.php
│   │       │   │           ├── switch.blade.php
│   │       │   │           └── textarea.blade.php
│   │       │   ├── image/
│   │       │   │   └── index.blade.php
│   │       │   ├── modal/
│   │       │   │   ├── confirm.blade.php
│   │       │   │   └── index.blade.php
│   │       │   ├── pagination/
│   │       │   │   └── index.blade.php
│   │       │   ├── section/
│   │       │   │   └── index.blade.php
│   │       │   ├── skeleton/
│   │       │   │   └── index.blade.php
│   │       │   ├── spinner/
│   │       │   │   └── index.blade.php
│   │       │   ├── table/
│   │       │   │   ├── index.blade.php
│   │       │   │   ├── tbody.blade.php
│   │       │   │   ├── td.blade.php
│   │       │   │   ├── th.blade.php
│   │       │   │   ├── thead.blade.php
│   │       │   │   └── tr.blade.php
│   │       │   └── tabs/
│   │       │       ├── index.blade.php
│   │       │       └── item.blade.php
│   │       ├── home/
│   │       │   └── index.blade.php
│   │       └── layouts/
│   │           ├── base.blade.php
│   │           └── master.blade.php
│   ├── Routes/
│   │   └── web-routes.php
│   ├── Sections/
│   │   ├── SectionDefinition.php
│   │   └── SectionRegistry.php
│   └── Seo/
│       └── SeoService.php
```

---

## 58. Target Presentation Package Tree

Proposed final directory tree for `packages/Webkul/Website/`:

```text
packages/Webkul/Website/
├── composer.json
├── src/
│   ├── Contracts/
│   │   └── SiteDefinitionContract.php
│   ├── Http/
│   │   └── Controllers/
│   │       ├── AboutController.php
│   │       ├── HomeController.php
│   │       └── Integrations/
│   │           └── LostAndFoundController.php
│   ├── Providers/
│   │   └── WebsiteServiceProvider.php
│   ├── Resources/
│   │   ├── assets/
│   │   │   ├── css/
│   │   │   │   └── website.css
│   │   │   └── images/
│   │   │       ├── favicon.ico
│   │   │       └── logo.svg
│   │   ├── lang/
│   │   │   ├── ar/app.php
│   │   │   └── en/app.php
│   │   └── views/
│   │       ├── layouts/
│   │       │   └── master.blade.php
│   │       ├── lost-found/
│   │       │   ├── index.blade.php
│   │       │   └── show.blade.php
│   │       ├── pages/
│   │       │   └── about.blade.php
│   │       ├── partials/
│   │       │   ├── footer.blade.php
│   │       │   └── header.blade.php
│   │       └── sections/
│   │           ├── announcements.blade.php
│   │           ├── features.blade.php
│   │           ├── hero.blade.php
│   │           └── lost-found.blade.php
│   ├── Routes/
│   │   └── website-routes.php
│   └── SiteDefinition/
│       └── SiteDefinition.php
└── tests/
    └── Feature/
        ├── WebsiteLostAndFoundIntegrationTest.php
        ├── WebsitePackageTest.php
        ├── WebsiteShellChromeTest.php
        └── WebsiteSiteDefinitionTest.php
```

---

## 59. Final Ownership Matrix

| Responsibility Area | Authority Owner | Implementation Artifact |
|---|---|---|
| **Base Document Shell** | `Webkul\Web` | `web::layouts.base` |
| **Final Site Master Layout** | `Webkul\Website` | `website::layouts.master` |
| **Component Behavioral Contracts**| `Webkul\Web` | `<x-web::*>` Blade templates |
| **Component Interaction State** | `Webkul\Web` | Vue runtime (`web-interactions.js`) |
| **Component Focus / A11y / ARIA** | `Webkul\Web` | Pure Web Blade & Vue |
| **Neutral Fallback Styling** | `Webkul\Web` | `web-fallback.css` |
| **Final Component Visuals** | `Webkul\Website` | `website.css` (`.web-*` styling) |
| **Branding, Logos, Favicons** | `Webkul\Website` | `SiteDefinition` & `website/assets/` |
| **Site Fonts & Typography** | `Webkul\Website` | `website.css` (`font-sans`, `@font-face`)|
| **Site Header & Footer Chrome** | `Webkul\Website` | `website::partials.header`, `footer` |
| **Navigation Registry Contract** | `Webkul\Web` | `NavigationRegistryContract` |
| **Site Navigation Appearance** | `Webkul\Website` | `website::partials.header` |
| **Section Registry Contract** | `Webkul\Web` | `SectionRegistryContract` |
| **Site Homepage Sections** | `Webkul\Website` | `website::sections.*` |
| **SEO Contract & Infrastructure**| `Webkul\Web` | `SeoMetadataContract`, `SeoService` |
| **Site SEO Defaults** | `Webkul\Website` | `SiteDefinition::getSeoMetadata()` |
| **Domain Public Contracts & DTOs**| Domain (`LostAndFound`)| `LostItemDto`, `ClaimServiceContract` |
| **Domain Presentation Integration**| `Webkul\Website` | `website::lost-found.*` views |
| **Root Build Orchestration** | Root Application | `vite.config.js`, `tailwind.config.js` |

---

## 60. Target Rendering Flow

```text
1. REQUEST
   GET /lost-found?q=backpack
   ↓
2. ROUTE & CONTROLLER (Website)
   Webkul\Website\Http\Controllers\Integrations\LostAndFoundController@index
   ↓
3. DOMAIN SERVICE QUERY (LostAndFound)
   LostAndFoundPublicService::search(CriteriaDto) -> LengthAwarePaginator<LostItemDto>
   ↓
4. VIEW COMPOSITION (Website)
   website::lost-found.index
   ├── extends website::layouts.master
   │    └── extends web::layouts.base
   │         ├── renders <head>, WebContext (dir="ltr|rtl"), SEO
   │         ├── renders skip-link
   │         ├── mounts <div id="app">
   │         └── loads website.css & web-interactions.js
   ├── renders website::partials.header
   │    └── renders <x-web::drawer id="website-mobile-drawer">
   ├── renders search form via Web Primitives
   │    <x-web::form method="GET">
   │        <x-web::form.control-group>
   │            <x-web::form.control-group.label>
   │            <x-web::form.control-group.control type="text" name="q" />
   │        </x-web::form.control-group>
   │        <x-web::form.control-group>
   │            <x-web::form.control-group.control type="select" name="category" />
   │        </x-web::form.control-group>
   │        <x-web::button type="submit">Search</x-web::button>
   │    </x-web::form>
   ├── renders items grid via <x-web::card>
   ├── renders pagination via <x-web::pagination :paginator="$results" />
   └── renders website::partials.footer
   ↓
5. RESPONSE: Accessible, fully rendered HTML enhanced by Vue
```

---

## 61. Target Component Consumption Model

How Presentation styles a Web primitive without touching its behavior:

```blade
{{-- In website::partials.header --}}
<x-web::drawer id="website-mobile-drawer" placement="end">
    <x-slot:header>
        <div class="flex items-center gap-2">
            <img src="{{ $siteDefinition->logoUrl }}" alt="{{ $siteDefinition->logoAlt }}" class="h-7 w-auto">
            <span class="font-bold text-slate-900">{{ $siteDefinition->name }}</span>
        </div>
    </x-slot:header>

    <nav class="flex flex-col gap-2 py-4">
        @foreach ($headerItems as $item)
            <a href="{{ $item->url }}" class="text-slate-700 hover:text-blue-600 font-semibold py-2">
                {{ $navigationLabels->resolve($item->title) }}
            </a>
        @endforeach
    </nav>
</x-web::drawer>
```

In `website.css`:
```css
/* Presentation controls the drawer appearance via standard CSS */
.web-drawer__dialog {
    border-radius: 1.5rem 0 0 1.5rem;
    box-shadow: var(--website-shadow-xl);
}
```

Web manages: Focus trap, Escape key, open/close state, ARIA attributes, body scroll lock.
Website manages: Colors, logo, navigation links, padding, border radius.

---

## 62. Target Form API

Canonical compound API achieving 100% Bagisto Shop and Admin parity:

```blade
<x-web::form method="POST" action="{{ route('student.claim.store') }}" enctype="multipart/form-data">
    <x-web::form.control-group>
        <x-web::form.control-group.label for="description" required>
            Detailed Claim Description
        </x-web::form.control-group.label>

        <x-web::form.control-group.control
            type="textarea"
            name="description"
            rows="4"
            placeholder="Describe unique identifiers, scratches, or contents..."
            required
        />

        <x-web::form.control-group.hint name="description">
            Be as specific as possible to verify ownership.
        </x-web::form.control-group.hint>

        <x-web::form.control-group.error control-name="description" />
    </x-web::form.control-group>

    <x-web::form.control-group>
        <x-web::form.control-group.label for="verification_doc">
            Supporting Document
        </x-web::form.control-group.label>

        <x-web::form.control-group.control
            type="file"
            name="verification_doc"
            accept="image/*,.pdf"
        />

        <x-web::form.control-group.error control-name="verification_doc" />
    </x-web::form.control-group>

    <x-web::button type="submit" variant="primary">
        Submit Claim Request
    </x-web::button>
</x-web::form>
```

---

## 63. Target Layout API

### Generic Base Shell (`web::layouts.base`):
```blade
<!DOCTYPE html>
<html lang="{{ $webContext->locale() }}" dir="{{ $webContext->direction() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>
    @stack('meta')
    @stack('styles')
</head>
<body class="web-document">
    <a href="#main-content" class="web-skip-link">Skip to main content</a>
    <div id="app">
        {{ $slot }}
    </div>
    @stack('scripts')
</body>
</html>
```

### Presentation Master Layout (`website::layouts.master`):
```blade
<x-web::layouts.base :title="$title ?? $siteDefinition->name">
    @push('styles')
        @vite(['packages/Webkul/Website/src/Resources/assets/css/website.css'])
    @endpush

    <div class="min-h-screen flex flex-col bg-slate-50">
        @include('website::partials.header')

        <main id="main-content" class="flex-1">
            {{ $slot ?? '' }}
            @yield('content')
        </main>

        @include('website::partials.footer')
    </div>
</x-web::layouts.base>
```

---

## 64. Styling Contract

Web component classes are classified for Presentation developers:

```text
STABLE PRESENTATION HOOKS (Safe for Website to Style):
├── .web-button, .web-button--{variant}, .web-button--{size}
├── .web-card, .web-card__header, .web-card__content, .web-card__footer
├── .web-badge, .web-badge--{variant}
├── .web-alert, .web-alert--{variant}
├── .web-form-group
├── .web-control-label, .web-control-label--required
├── .web-control-input, .web-control-select, .web-control-textarea
├── .web-control-checkbox, .web-control-radio, .web-control-switch
├── .web-control-error, .web-control-hint
├── .web-accordion, .web-accordion__item, .web-accordion__trigger, .web-accordion__panel
├── .web-modal, .web-modal__backdrop, .web-modal__dialog, .web-modal__header, .web-modal__footer
├── .web-drawer, .web-drawer__backdrop, .web-drawer__dialog
└── .web-pagination, .web-pagination__link, .web-pagination__link--active

DYNAMIC STATE HOOKS:
├── .is-disabled, [aria-disabled="true"]
├── .is-invalid, [aria-invalid="true"]
├── [aria-expanded="true"]
├── [aria-selected="true"]
└── [hidden]

BEHAVIOR TARGETS (FORBIDDEN FOR CSS STYLING):
├── [data-web-modal-trigger]
├── [data-web-drawer-trigger]
├── [data-web-dropdown-trigger]
└── [data-web-accordion-trigger]
```

---

## 65. Generic Fallback Contract

`packages/Webkul/Web/src/Resources/assets/css/web-fallback.css` provides:
1. Complete, functional styling for all 22 P0/P1 components.
2. WCAG AA color contrast ratios for text and focus indicators.
3. Logical CSS properties (`margin-inline`, `padding-inline`, `border-inline-start`, `inset-inline-start`) guaranteeing RTL fidelity without physical overrides.
4. Minimal footprint: ~12–15 kB compiled.
5. In the physical absence of `Webkul\Website`, CampusFind boots into `web::layouts.master` styled entirely by `web-fallback.css`.

---

## 66. Build Ownership

- **Root Application** owns `package.json`, `vite.config.js`, `tailwind.config.js`, `postcss.config.js`.
- **Output**: Single compiled bundle in `public/build/` referenced by `public/build/manifest.json`.
- **Command**: `npm run build`.

---

## 67. Root Build Portability

The audit resolves `ROOT_BUILD_KNOWS_WEBSITE`:
- In standard Laravel application architecture, the root repository is the **explicit composition root**.
- It is architecturally sound for the root application to explicitly know the presentation package it is configured to assemble.
- What is forbidden is for `Webkul\Web` to know `Webkul\Website` (`Web -> Website = 0`).
- Model A (Explicit Root Composition) is confirmed as the target build model.

---

## 68. Explicit Package Composition

Per Section 61, filesystem feature detection (`fs.existsSync`) is **rejected** as composition logic.
Package composition must be explicit:
- Service providers are registered in `bootstrap/providers.php`.
- Frontend entry points are explicitly declared in `vite.config.js`.
- Tailwind scan paths are explicitly declared in `tailwind.config.js`.
- If a presentation package is replaced, the root application config is explicitly updated by the developer.

---

## 69. Physical Removability

The blueprint guarantees that `Webkul\Website`, `Webkul\Student`, and `Webkul\LostAndFound` can each be physically deleted:
1. `Foundation + Web - Website`: Boots, renders `web::home.index` via `web::layouts.master` with `web-fallback.css`. Tests pass.
2. `Foundation + Web + Website - LostAndFound`: Boots, renders site homepage without the lost-found section, search routes return 404 cleanly.
3. `Foundation + Web + Website - Student`: Boots, renders site pages without student portal links.

---

## 70. Admin Boundary

- Admin UI resides in `packages/Webkul/Admin/`.
- Admin owns its own pipeline, middleware (`['web', 'admin_locale', 'user', 'bouncer']`), views (`admin::*`), and assets (`admin.css`, `admin.js`).
- Admin and Web share structural vocabulary (`form.control-group`) but share zero runtime code, zero components, and zero routes.

---

## 71. DataGrid Boundary

- `packages/Webkul/DataGrid/` owns SQL query building, filtering, column definitions, sort validation, and mass action pipelines.
- `packages/Webkul/Web/` owns `<x-web::table>` (semantic HTML table wrapper) and `<x-web::pagination>` (presentation of paginator links).
- Web does NOT execute database queries or manage filter state.

---

## 72. Domain Boundary

- Domain packages (`Student`, `LostAndFound`) own models, business workflows, repositories, state machines, and private database columns.
- Domain packages expose read-only Data Transfer Objects (DTOs) and public services.
- Presentation packages consume domain DTOs to render visual pages.
- Web never receives domain entities or models.

---

## 73. Security Considerations

1. **Content Security Policy (CSP)**: Eliminating Bagisto's inline `<script type="module">` tags allows enforcing strict `script-src 'self'`.
2. **XSS Protection**: All Blade slots escape content by default (`{{ $slot }}`).
3. **CSRF Protection**: `<x-web::form>` automatically outputs `@csrf` for state-changing HTTP verbs (`POST`, `PUT`, `PATCH`, `DELETE`).
4. **Input Sanitization**: Control components enforce input type restrictions and length bounds.

---

## 74. Accessibility Considerations

1. **Focus Trap & Restoration**: All overlays (`Modal`, `Drawer`) trap focus inside the dialog and restore focus to the triggering element upon close.
2. **Escape Key Handling**: Escape key is handled globally on `document`, ensuring overlays close even if focus lands on backdrops.
3. **Form Association**: Every `<label>` is explicitly linked to its input via `for="..."` and `id="..."`. Error messages and hint texts are linked via `aria-describedby`. Invalid inputs carry `aria-invalid="true"`.
4. **WAI-ARIA Tabs**: Full roving tabindex and arrow-key navigation between tabs.
5. **Reduced Motion**: All CSS transitions honor `@media (prefers-reduced-motion: reduce)`.

---

## 75. Performance Considerations

1. **Single Vue Mount**: One compiler-enabled Vue instance mounted over `#app`.
2. **Centralized Event Delegation**: Moving overlay click and keydown handlers to a centralized overlay manager eliminates listener proliferation.
3. **Zero Layout Thrashing**: Eliminating Bagisto's synchronous DOM dimension queries prevents forced reflows.
4. **Zero VeeValidate Overhead**: Eliminating VeeValidate saves ~45 kB of JavaScript and avoids client-side schema parsing.

---

## 76. Exact Reconstruction Roadmap

The implementation roadmap is derived from the decomposition:

```text
Phase 15 Step 07B — Web & Presentation Package Structural Reconstruction
                     Reorganize Web components into nested family directories.
                     Explicit Blade::anonymousComponentPath registration.
                     Establish base document shell <x-web::layouts.base>.

Phase 15 Step 08  — Web Form Foundation (Control-Group Architecture)
                     Implement <x-web::form>, <x-web::form.control-group>,
                     .label, .control (text, select, textarea, checkbox, radio, switch),
                     .error, and .hint. Wire automatic old() and $errors.

Phase 15 Step 09  — Feedback & Navigation Primitives
                     Implement <x-web::spinner>, <x-web::pagination>, <x-web::flash>,
                     <x-web::skeleton>, and <x-web::breadcrumbs>.
                     Harden <x-web::button> (loading state).

Phase 15 Step 10  — Interactive Overlay Hardening & Centralized Listener Manager
                     Harden <x-web::modal>, <x-web::drawer>, <x-web::dropdown>.
                     Implement Centralized Overlay Manager in JS.
                     Add neutral BEM styles to web-fallback.css.

Phase 15 Step 11  — Disclosure & Data Primitives
                     Implement WAI-ARIA <x-web::tabs>, <x-web::table>, <x-web::image>,
                     <x-web::empty-state>, <x-web::container>, <x-web::section>.

Phase 15 Step 12  — Website Presentation Migration
                     Refactor Website views to consume Web primitives.
                     Eliminate raw HTML forms and custom pagination in lost-found views.

Phase 15 Step 13  — Performance, Accessibility & Root Build Certification
                     Full regression testing, WCAG AA compliance certification.
```

---

## 77. Production Source Integrity

- Production PHP source changed: **NO**
- Blade views changed: **NO**
- CSS / JS changed: **NO**
- Database schema changed: **NO**
- Dependencies changed: **NO**
- Git diff check: **PASSED (CLEAN)**

---

## 78. Risks

1. **Premature Component Expansion**: Adding components before establishing the `control-group` directory structure causes path churn. Step 07B must establish the physical directories first.
2. **Loss of Server-Side Simplicity**: Reintroducing client-side form libraries would damage progressive enhancement. Keep form controls pure Blade.

---

## 79. Blockers

- **Zero architectural blockers**.
- All dependencies, tests, baseline metrics, and boundary constraints are fully satisfied.

---

## 80. Machine-Readable Certification

```text
PHASE_15_STEP_07A_STATUS=CERTIFIED_COMPLETE

PRODUCTION_SOURCE_CHANGED=NO
DATABASE_CHANGED=NO
DEPENDENCIES_CHANGED=NO

BAGISTO_REPOSITORY=bagisto/bagisto
BAGISTO_VERSION=2.4.12
BAGISTO_BRANCH=v2.4.12-release
BAGISTO_COMMIT=unpacked-release-archive
AUDIT_DATE=2026-10-01

BAGISTO_SHOP_FILES_AUDITED=559
BAGISTO_SHOP_COMPONENT_FAMILIES=23

BAGISTO_SHOP_DECOMPOSED_BY_RESPONSIBILITY=YES

WEB_INFRASTRUCTURE_CONCERNS_IDENTIFIED=16
PRESENTATION_CONCERNS_IDENTIFIED=8
COMMERCE_CONCERNS_IDENTIFIED=12
THEME_CONCERNS_IDENTIFIED=6

CURRENT_WEB_COMPONENT_FAMILIES=9
CURRENT_WEB_COMPONENT_FILES=14

WEB_IS_FINAL_PRESENTATION_OWNER=NO

WEB_ROLE=UI_INFRASTRUCTURE_KERNEL
PRESENTATION_ROLE=VISUAL_EXPERIENCE_LAYER
DOMAIN_ROLE=BUSINESS_LOGIC_AND_DATA

STRUCTURAL_PARITY_MODEL=BAGISTO_SHOP_AND_ADMIN_PARITY
COMPONENT_COMPOSITION_MODEL=CONTROL_GROUP_COMPOUND_ARCHITECTURE
BLADE_VUE_MODEL=BLADE_OWNED_MARKUP_VUE_OWNED_INTERACTIONS

FORM_API_RECOMMENDATION=CONTROL_GROUP_COMPOUND_API
LAYOUT_API_RECOMMENDATION=BIFURCATED_WEB_BASE_SHELL_AND_WEBSITE_MASTER

WEB_CSS_ARCHITECTURE_RECOMMENDATION=NEUTRAL_BEM_FALLBACK_WITH_LOGICAL_PROPERTIES
PRESENTATION_CSS_ARCHITECTURE_RECOMMENDATION=TAILWIND_DESIGN_TOKENS_AND_VISUAL_HOOKS

WEB_ASSET_OWNERSHIP_RECOMMENDATION=GENERIC_INTERACTION_JS_AND_FALLBACK_CSS
PRESENTATION_ASSET_OWNERSHIP_RECOMMENDATION=BRAND_CSS_FONTS_IMAGES_AND_SITE_ASSETS

BUILD_ARCHITECTURE_RECOMMENDATION=MODEL_A_EXPLICIT_ROOT_COMPOSITION
TAILWIND_ARCHITECTURE_RECOMMENDATION=ROOT_TAILWIND_SCANNING_WEB_AND_PRESENTATION

VEEVALIDATE_DECISION=REJECTED_IN_FAVOR_OF_NATIVE_LARAVEL_VALIDATION
INLINE_TEMPLATE_DECISION=REJECTED_IN_FAVOR_OF_VITE_PRECOMPILED_RUNTIME

WEB_OWNS_BRANDING=NO
WEB_OWNS_FINAL_LAYOUTS=NO
WEB_OWNS_FINAL_PAGES=NO
WEB_OWNS_HEADER_FOOTER=NO
WEB_OWNS_FINAL_COMPONENT_STYLING=NO

WEB_OWNS_COMPONENT_CONTRACTS=YES
WEB_OWNS_GENERIC_BEHAVIOR=YES
WEB_OWNS_ACCESSIBILITY=YES
WEB_OWNS_GENERIC_INTERACTION_JS=YES

PRESENTATION_OWNS_BRANDING=YES
PRESENTATION_OWNS_FINAL_LAYOUTS=YES
PRESENTATION_OWNS_FINAL_PAGES=YES
PRESENTATION_OWNS_HEADER_FOOTER=YES
PRESENTATION_OWNS_FINAL_COMPONENT_STYLING=YES

PRESENTATION_TO_WEB_DEPENDENCY=YES
WEB_TO_PRESENTATION_DEPENDENCY=0

DOMAIN_TO_PRESENTATION_DEPENDENCY=0

WEB_TO_WEBSITE_REFS=0
WEB_TO_STUDENT_REFS=0
WEB_TO_LOST_FOUND_REFS=0

THEME_ENGINE_PRESENT=NO
THEME_ENGINE_REQUIRED_BY_TARGET_WEB=NO
THEME_ENGINE_REQUIRED_BY_PRESENTATION=NO

WEB_USABLE_WITHOUT_PRESENTATION=YES
PRESENTATION_REPLACEABLE_WITHOUT_WEB_CHANGES=YES
DOMAIN_INDEPENDENT_OF_PRESENTATION=YES

ROOT_BUILD_KNOWS_WEBSITE=YES
TARGET_ROOT_BUILD_KNOWS_WEBSITE=YES_VIA_EXPLICIT_APPLICATION_COMPOSITION

FILESYSTEM_FEATURE_DETECTION_RECOMMENDED=NO

TARGET_WEB_PACKAGE_TREE_DEFINED=YES
TARGET_PRESENTATION_PACKAGE_TREE_DEFINED=YES

TARGET_COMPONENT_API_DEFINED=YES
TARGET_FORM_API_DEFINED=YES
TARGET_LAYOUT_API_DEFINED=YES

TARGET_WEB_ASSET_ARCHITECTURE_DEFINED=YES
TARGET_PRESENTATION_ASSET_ARCHITECTURE_DEFINED=YES
TARGET_BUILD_ARCHITECTURE_DEFINED=YES

TARGET_RENDERING_FLOW_DEFINED=YES
BEHAVIOR_PRESENTATION_BOUNDARY_DEFINED=YES

PHYSICAL_PRESENTATION_REMOVABILITY_PRESERVED=YES
PHYSICAL_DOMAIN_PACKAGE_REMOVABILITY_PRESERVED=YES

PRODUCTION_SOURCE_INTEGRITY=VERIFIED_UNCHANGED
GIT_DIFF_CHECK=CLEAN

NEXT_STEP=PHASE_15_STEP_07B_WEB_PRESENTATION_STRUCTURAL_RECONSTRUCTION
```
