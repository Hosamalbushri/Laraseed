# 10. Admin / Web Presentation Boundary and Infrastructure Rules

## Status
Mandatory Foundation Architectural Rule.

---

## 1. Fundamental Principle: Presentation Separation
`Admin` and `Web` are distinct, independent presentation layers.
- **`Webkul\Admin`**: Admin Presentation Infrastructure (for staff and privileged users).
- **`Webkul\Web`**: Web Presentation Infrastructure (for public portal visitors, students, and guests).
- **`Webkul\Theme`**: Visual rendering and theme engine (consumed by `Web`).
- **Business Packages (`Student`, `Event`, `LostAndFound`)**: Owners of their respective domain-specific presentation on both Admin and Web surfaces.

```text
ADMIN ≠ WEB
```

---

## 2. Dependency Direction Rules

1. **Foundation Presentation to Domain Separation**:
   - `Webkul\Admin` MUST NOT depend on `Webkul\Student`, `Webkul\Event`, `Webkul\LostAndFound`, or `Webkul\Web`.
   - `Webkul\Web` MUST NOT depend on `Webkul\Student`, `Webkul\Event`, `Webkul\LostAndFound`, or `Webkul\Admin`.
2. **Domain to Presentation Direction**:
   - Domain packages register their presentation contributions via generic contracts (`NavigationRegistryContract`, `SectionRegistryContract`, `ViewRenderEventManager`, DataGrids, CoreConfig, Menu, ACL).
3. **Theme Dependency**:
   - Themes depend on `Webkul\Web` contracts and view structures. `Webkul\Web` NEVER depends on a specific Theme package.
4. **Ecommerce Prohibition**:
   - Neither `Admin` nor `Web` may contain ecommerce concepts (carts, checkouts, payment gateways, products, orders, customers, shop themes).

---

## 3. Pipeline Separation

| Dimension | Admin Pipeline | Web Pipeline |
| :--- | :--- | :--- |
| **Middleware** | `['web', 'admin_locale', 'user', 'bouncer']` | `['web', 'web_context']` |
| **Locale Authority** | Admin config / employee preference | `ContentLocaleService` + dynamic session / cookie |
| **View Namespace** | `admin::*` | `web::*` |
| **Translation Namespace** | `admin::*` | `web::*` |
| **Authentication Guard** | `user` | Optional / none (public visitor) |
| **Navigation Registry** | `core()->getAdminMenu()` / `menu.admin` | `NavigationRegistryContract` |
| **Layout Shell** | `admin::layouts.master` | `web::layouts.master` |

---

## 4. Verification and Enforcement
All changes must be verified against automated boundary isolation tests ensuring:
1. Production code in `packages/Webkul/Web` contains 0 imports or references to forbidden packages.
2. Web routes never execute Admin authentication or ACL middleware.
3. Translations and views remain strictly namespaced under `web::`.

## 5. Generic Public Root Ownership

The generic public root route `/` belongs to `Webkul\Web` and must render through the Web context, locale, and theme pipeline. Optional business packages must never claim the generic root or become required for it to function. Student authentication remains available through Student-owned routes, but Student login is not the website homepage. Themes and the Theme Engine never own application routes.

## 6. Request-Locale-Safe Public Navigation

Optional packages may register public navigation definitions during provider boot through `NavigationRegistryContract`. Registration must remain request-locale-neutral: providers register either a static text label or an immutable translation-key definition and must never translate request-specific public navigation labels during boot.

Localized labels resolve only after `web_context` establishes the current Web locale. A singleton navigation registry stores definitions only; it must never retain a request locale or a resolved translated label. The generic Web resolver owns translation semantics, while themes only request the resolved text and preserve escaped output. Web must not know which optional business package contributed a definition.

## 7. Optional-Package Public Web Ownership and Removability

An optional business package that exposes public pages owns its public route file, controllers, bounded read operations, views, static translations, SEO content, and navigation contributions. It may consume the generic Web middleware, layout, components, SEO contract, and navigation contracts through an explicit package dependency on `Webkul\Web`.

The Web package, Theme Engine, and production themes must remain unaware of the optional package. They must not register its routes, include its views, translate its labels, hardcode its navigation links, query its data, or add package-specific styling hooks. Public routes and navigation must therefore disappear when the optional package provider is unregistered, without edits to Web, Theme, or a production theme. Theme overrides remain presentation-only and may target a package view through the generic view-finder contract without transferring business ownership to the theme.
