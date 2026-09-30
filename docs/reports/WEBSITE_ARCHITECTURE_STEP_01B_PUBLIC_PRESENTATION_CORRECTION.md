# CampusHub — Website Architecture: Step 01-B Public Presentation Ownership Correction

**Date:** 2026-09-29  
**Mode:** ARCHITECTURE CORRECTION ONLY (No source code modifications, no file moves)  
**Status:** COMPLETE  

---

## 1. Executive Summary & Authoritative Target

This document formally corrects the architectural classification established in Step 01 regarding public-web presentation ownership for Business Packages.

### Authoritative Architecture Definition

```text
Web
= permanent generic public-web foundation
+ fully functional default public presentation

Theme
= generic presentation and theme infrastructure

Base Theme
= permanent safe default visual presentation

Website
= optional site-specific Web application and composition package

Business Packages (Event, Student, LostAndFound)
= business capabilities that do NOT independently decide
  how they appear on the main public website
```

### Governing Invariant
> **A Business Package can be fully operational without having any main-site public presentation. The optional Website package owns the decision and implementation of exposing that Business Package on a customized public website.**

This invariant is **ACCEPTED AND CONFIRMED AS MANDATORY ARCHITECTURAL LAW**.

---

## 2. Re-evaluation of Step 01 Flaw

In Step 01, the existing public Web implementation inside `Webkul\Event` (`/events`, `/events/{event}`, `Web\EventController`, `event::web.*` views, and Web navigation registration) was classified as:
```text
ALREADY_COMPATIBLE (Event owns default public pages)
```

**This classification was architecturally incorrect.**

Under the true decoupled architecture:
1. A Business Package must not unilaterally deposit routes, navigation items, controllers, or views onto the main public website.
2. If enabling `Event` automatically registers `/events` and adds "Events" to the main site navigation without `Website` being installed, then the public website's presentation has been hijacked by a business module.
3. If an organization runs CampusHub purely as an administrative portal, or has a public website that should not display public events, `Event` must remain 100% operational in Admin and API without exposing any public web surface.
4. Therefore, the presence of public-site routes, navigation, and controllers inside `packages/Webkul/Event` represents a **STRUCTURAL CONFLICT** that must be resolved in future implementation waves by transferring presentation ownership to `Website` (or a Website Event Adapter).

---

## 3. Business Package Public Presentation Law

The target architecture strictly prohibits:
```text
Event ─────────► Web (for main-site presentation)
Student ───────► Web (for main-site presentation)
LostAndFound ──► Web (for main-site presentation)
```

Business Packages must not independently register:
- Main-site routes (e.g. `/events`, `/student/directory`)
- Main-site navigation items (e.g. adding items to `NavigationRegistryContract`)
- Main-site views and layouts
- Main-site page sections (e.g. automatically registering homepage sections)
- Main-site presentation controllers
- Main-site theme integrations

Instead, the dependency direction is:
```text
Website ────────► Event (consumes public read contract)
Website ────────► Student (consumes public read contract)
Website ────────► LostAndFound (consumes public read contract)
```
when and only when that particular `Website` requires those capabilities.

---

## 4. Valid State: Website ABSENT + Event PRESENT

The following state is completely valid and supported:

```text
Web:       ENABLED (Foundation)
Theme:     ENABLED (Foundation)
Base:      ENABLED (Theme)
Event:     ENABLED (Optional Business Package)
Website:   ABSENT  (Uncomposed / Disabled)
```

### Expected Behavior
- **Application Boot**: PASS
- **Admin**: PASS (`/admin/events` management, DataGrids, CRUD, category trees, subscriptions all fully functional)
- **Event Domain & Persistence**: PASS (models, schemas, migrations, services operational)
- **Web Root `/`**: PASS (renders generic welcome page via Base theme)
- **Base Theme Presentation**: PASS (visual styling active)
- **Event Main-Site Routes**: **ABSENT** (`GET /events` returns 404)
- **Event Main-Site Navigation**: **ABSENT** (no "Events" link in header/mobile navigation)
- **Event Main-Site Pages & Views**: **ABSENT**
- **Event Main-Site Sections**: **ABSENT**

> [!IMPORTANT]
> The absence of Event public-site presentation when Website is absent is **NOT** a failure or defect. It is **intentional architectural isolation**.

---

## 5. Event Ownership Target: Capability vs Presentation Adapter

The target division of responsibilities is:

```text
packages/Webkul/Event/
├── Domain Models (Event, EventCategory, EventImage)
├── Application Services (EventWriteService, EventSubscriptionService)
├── Persistence & Migrations (tables, foreign keys, indexes)
├── Domain Policies & Invariants (availability rules, seat calculations)
├── Business Events (EventCreated, EventPublished)
├── Admin Presentation (Controllers, DataGrids, Views, Menu, ACL)
└── Stable Public Read Contract (EventPublicReadContract)
```

versus:

```text
packages/Webkul/Website/ (or Website Event Presentation Adapter)
├── Public Routes (e.g. Route::get('events', ...))
├── Public Controllers / Presenters (EventPageController)
├── Site Pages & Templates (event listing, event details)
├── Homepage Sections (Upcoming Events section registered in SectionRegistryContract)
├── Site Navigation (Events link registered in NavigationRegistryContract)
├── SEO Presentation (meta tags, OpenGraph tailored to the site)
└── Site-Specific Event Components (<x-website::event-card>)
```

This clean separation perfectly satisfies Rule 08 (Foundation & Package Isolation) and Rule 09 (Package Internal Architecture & Extension Rules).

---

## 6. Website Definition Expanded

`Website` is **not merely a homepage section contributor**.

`Website` is a **site-specific Web application and composition package**.

### What Website MAY Own:
- Site-specific routes (`/about`, `/contact`, `/events`, `/campus-life`)
- Site-specific controllers and presenters
- Site pages and views (`website::*`)
- Site layouts extending or wrapping `web::layouts.master`
- Site-specific composite components (`<x-website::hero>`, `<x-website::event-card>`)
- Site sections contributed to `SectionRegistryContract`
- Site navigation trees contributed to `NavigationRegistryContract`
- Site SEO metadata presentation
- Site localization keys (`website::*`)
- Site assets and stylesheets (or accompanying site theme)
- Business-package presentation adapters

### What Website MUST NOT Own:
- Business entities and Eloquent models
- Database tables and migrations
- Business repositories or write operations
- Business transactions
- Business authorization rules (e.g. Bouncer roles, domain capabilities)
- Business domain invariants

---

## 7. Public Read Contracts

The interaction between `Website` and any Business Package must cross a strict contract boundary:

```text
Website (Presentation Consumer)
     │
     ▼
EventPublicReadContract (Interface owned by Event)
     ▲
     │
EventRepository / ReadService (Implementation owned by Event)
```

### Invariants for `EventPublicReadContract`
1. **Data and Query Operations Only**:
   - Exposes methods such as `paginatePublicEvents(int $perPage)` returning paginated collections or DTOs.
   - Exposes `findPublicEvent(int $id)` applying domain availability and publication scopes.
   - Exposes `getUpcomingEvents(int $limit)` for widgets and homepage sections.
2. **Zero Presentation Knowledge**:
   - The contract MUST NOT return Blade view names or view instances.
   - The contract MUST NOT return HTML strings or CSS classes.
   - The contract MUST NOT reference navigation locations (`header`, `mobile`).
   - The contract MUST NOT reference homepage section keys.
   - The contract MUST NOT reference theme names or web route names.

---

## 8. Corrected Removability Matrix

| Case | State | Boot | Root `/` | Base Theme | Event Business / Admin | Event Main-Site Presentation | Website Custom Pages |
|---|---|---|---|---|---|---|---|
| **A** | Web + Theme/Base<br>Website Absent<br>Event Absent | PASS | PASS (Default) | PASS | ABSENT | ABSENT | ABSENT |
| **B** | Web + Theme/Base<br>Website Absent<br>Event Enabled | PASS | PASS (Default) | PASS | **PASS** (Full Admin & Domain) | **ABSENT** (Clean Isolation) | ABSENT |
| **C** | Web + Theme/Base<br>Website Enabled<br>Event Enabled<br>Website requires Event | PASS | PASS (Custom Home) | PASS | **PASS** | **PASS** (Exposed via Website) | **PASS** |
| **D** | Website Removed<br>Event Remains Enabled | PASS | PASS (Reverts to Default) | PASS | **PASS** (Intact Admin & Domain) | **ABSENT** (Instantly Gone) | ABSENT |
| **E** | Website Enabled (declares Event dependency)<br>Event Disabled | **COMPOSITION FAILURE** | N/A | N/A | N/A | N/A | N/A |

*Note on Case E: In accordance with Rule 08 Section 11, invalid package composition throws `InvalidPackageComposition` at configuration load time. It fails fast and deterministically rather than degrading into a runtime error.*

---

## 9. Reclassified Conflicts Table

| Current State | Target Owner | Classification | Reason | Future Action (Post Step 01) |
|---|---|---|---|---|
| **Event `composer.json` requires `"webkul/web"`** | `Website` requires `Web` | **STRUCTURAL_CONFLICT** | Event only needs Web because it currently owns main-site presentation routes and navigation. | Remove `"webkul/web"` from Event's dependencies when presentation is extracted. |
| **Event `/events` and `/events/{event}` routes** | `Website` | **STRUCTURAL_CONFLICT** | Business package must not unilaterally register main-site public routes. | Move public event routes to `Website`. |
| **Event Web controller (`Http/Controllers/Web/EventController.php`)** | `Website` | **STRUCTURAL_CONFLICT** | Public presentation controllers belong to the site-specific composition layer. | Move controller to `Website`. |
| **Event Web views (`Resources/views/web/*`)** | `Website` | **STRUCTURAL_CONFLICT** | Blade views consuming `web::layouts.master` and rendering public event pages belong to the site. | Move views to `Website` (under `website::events.*`). |
| **Event Web navigation registration in `EventServiceProvider`** | `Website` | **STRUCTURAL_CONFLICT** | Business packages must not inject menu entries into main-site navigation during provider boot. | Remove `registerWebNavigation()` from `EventServiceProvider`; `Website` handles nav. |
| **Event public repository reads (`paginatePublic`, `findPublicOrFail`)** | `Event` | **MINOR_CONFLICT** | Query logic belongs in Event, but lacks a formal `EventPublicReadContract`. | Extract and bind `EventPublicReadContract` in `Event`. |
| **Web root `/` (`web.home`)** | `Web` | **ALREADY_COMPATIBLE** | Web owns generic root with clean fallback to Base theme; Website composes onto it. | Preserve current architecture. |
| **Base theme overrides** | `Base Theme` | **ALREADY_COMPATIBLE** | Base theme provides business-neutral default presentation. | Preserve current architecture. |

- **Structural Conflicts Identified**: **5** (all related to current Event public-web presentation).
- **Minor Conflicts Identified**: **1** (missing explicit contract for public read operations).

---

## 10. Dependency Target Summary

```text
PROHIBITED DEPENDENCIES:
Web          ─X─► Website
Theme        ─X─► Website
Base         ─X─► Website

Event        ─X─► Website
Student      ─X─► Website
LostAndFound ─X─► Website

Event        ─X─► Web (for main-site presentation)
Student      ─X─► Web (for main-site presentation)
LostAndFound ─X─► Web (for main-site presentation)

ALLOWED FORWARD COMPOSITION DEPENDENCIES:
Website      ────► Web (hard infrastructure dependency)
Website      ────► Theme infrastructure (consumed via Web / Theme engine)
Website      ────► Event (declared business dependency via EventPublicReadContract)
Website      ────► Student (declared business dependency via Student public contract)
Website      ────► LostAndFound (declared business dependency via LostAndFound public contract)
```

---

## 11. Architectural Conclusion & Next Step

1. **The Correction is Complete**: Step 01-B firmly re-establishes package isolation. Business packages own domain, persistence, business logic, Admin interfaces, and public read contracts. `Website` owns main-site public presentation, routing, navigation, and business adapters.
2. **Zero Over-Abstraction**: No runtime plugin engines, CMS systems, widget buses, or reflection lookups are introduced. Existing Laravel ServiceProviders, `OptionalPackageComposition`, `SectionRegistryContract`, and `ThemeViewFinder` are completely sufficient.
3. **No Code Modified in Step 01-B**: All source code, existing tests, routes, and Composer configurations remain intact.
4. **Readiness for Step 02**: Step 02 can now proceed with clear, unambiguous boundaries to define the `EventPublicReadContract` and the `Website` specification.

---
