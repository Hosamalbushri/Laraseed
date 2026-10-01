# Laraseed Admin UI Page Rules

> **Mandatory Admin presentation standard.** These rules govern pages rendered in the Admin control-panel shell, whether the page belongs to Foundation or to an optional package. Rendering in Admin does not transfer feature ownership to `Webkul\Admin`.

## 1. Ownership comes before presentation

`Webkul\Admin` owns the reusable control-panel shell, middleware, authorization infrastructure, components, generic configuration UI, and feature-neutral extension points. An optional package owns every Admin-facing controller, request, DataGrid, view, route, ACL entry, menu entry, configuration contribution, and translation that exists specifically for that package.

An Accounting page displayed with `x-admin::*` components still belongs under `Accounting/src`, not `Admin/src/Http/Controllers/Accounting` or `Admin/src/Resources/views/accounting`. The same rule applies to Student, Event, LostAndFound, Shop, and future features.

## 2. Reuse the Admin design system

Package-owned Admin pages SHOULD consume the established `x-admin::*` components, layout, form controls, DataGrid renderer, modal/dropdown primitives, styling tokens, middleware aliases, breadcrumbs, and authorization infrastructure. Packages MUST NOT copy generic components merely to customize feature behavior.

A package MUST NOT modify a generic Admin component to import its namespace, query its tables, call its routes, check its permissions, or include its views. If a reusable capability is genuinely missing, add the smallest feature-neutral and tested extension point.

Do not create a new shared Admin UI component when composition with an existing component, slot, DataGrid facility, view render event, or generic registry is sufficient.

## 3. Page structure and interaction contract

Admin pages must follow the existing layout and interaction conventions where applicable:

- use the Admin layout and package-owned translated page title;
- expose breadcrumbs owned by the package when the trail is feature-specific;
- use established buttons, forms, errors, tables/DataGrids, dark-mode classes, spacing, and responsive behavior;
- keep destructive actions explicit, authorized, and confirmation-protected;
- preserve validation input and show translated validation/flash feedback;
- escape user-controlled content unless it has passed an explicit sanitization boundary;
- keep state-changing actions out of GET requests and Blade templates.

Visual similarity does not justify copying an existing feature page. Reuse infrastructure and keep feature-specific markup in the feature package.

## 4. Controllers, requests, and authorization

An Admin controller should receive a validated request, obtain the authenticated actor, call an application/service/repository boundary, and return a response. Feature workflows, persistence rules, upload policy, and domain decisions do not belong in Blade or in a large controller.

Feature-specific FormRequests belong to the feature package. UI visibility is never authorization. Routes, ACL/Bouncer checks, application authorization, ownership checks, and domain invariants must remain effective when requests bypass the rendered UI.

## 5. DataGrids

Feature DataGrids belong to the feature package. They may use the established DataGrid query-builder architecture directly, including package-owned joins and calculated columns. This exception is limited to DataGrid read/query composition and does not authorize direct model querying throughout controllers or services.

A host DataGrid may expose generic query/column contribution events. The consuming package owns its listener and all feature-specific joins, labels, permissions, and columns.

## 6. Extension surfaces

When an optional package contributes to a generic Admin page, use a feature-neutral surface such as:

- a view render event;
- a generic registry for metrics, search tabs, actions, or navigation contributions;
- a stable event/listener contract;
- an explicit contribution interface.

Correct direction:

```text
Admin generic page -> generic extension point <- optional package contribution
```

Forbidden direction:

```text
Admin generic page -> event::view, Event model, Event route, or Event permission
```

Generic views must not discover optional packages with `class_exists`, `Schema::hasTable`, `Route::has`, string service locators, reflection, or filesystem scanning.

## 7. Configuration, menu, ACL, and translations

Admin owns aggregation and rendering infrastructure. Optional packages own their feature entries and contribute them from package bootstrap code. Feature configuration fields and their labels belong to the feature, even when displayed by Admin's generic configuration page.

Every visible feature string must use the feature translation namespace. Do not place feature labels in `admin::app` merely because the page uses the Admin shell. Follow the static/content localization split in Rule 06 and preserve package locale parity.

## 8. Accessibility, direction, and responsive behavior

Use semantic controls, associated labels, keyboard-operable interactions, meaningful focus behavior, and text alternatives where applicable. Do not encode meaning only with color or icons. Layout must remain usable at supported responsive breakpoints and under both LTR and RTL direction supplied by authoritative locale metadata.

## 9. Blade boundary

Blade renders prepared data. It must not perform persistence, domain transitions, authorization as the sole defense, arbitrary model queries, optional-package discovery, or service orchestration. Small presentation normalization is acceptable; business decisions are not.

## 10. Verification gate

Before an Admin page is complete, verify:

1. physical ownership matches the feature;
2. route, controller, request, DataGrid, ACL, menu, config, view, and translation ownership are correct;
3. direct URL/API authorization works independently of UI visibility;
4. validation, escaping, empty states, responsiveness, dark mode, and localization work;
5. generic Admin files contain no optional-package knowledge;
6. removing the optional package removes its contributions without Foundation source edits;
7. no old page, route, translation, alias, wrapper, or duplicate implementation remains.
