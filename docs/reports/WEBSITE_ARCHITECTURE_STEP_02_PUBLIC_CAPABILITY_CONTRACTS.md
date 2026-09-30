# CampusHub — Website Architecture: Step 02 Business Public Capability Contract Design

**Date:** 2026-09-29  
**Mode:** FORENSIC DESIGN + CONTRACT SPECIFICATION ONLY (No source code modifications, no file moves)  
**Status:** COMPLETE  

---

## 1. Executive Summary

This document establishes the contract boundary through which a future `Website` package consumes Business Package capabilities without depending on internal repositories, Eloquent active records, or presentation artifacts.

Using `Webkul\Event` as the first reference implementation:
1. **Contract Boundary**: `Webkul\Event\Contracts\EventPublicReadContract` is owned by `Event` and exposes strictly data and query operations. It has zero presentation knowledge (no Blade views, HTML, CSS, navigation targets, or route names).
2. **Return Type**: The contract returns immutable Read Model DTOs (`PublicEventData`) wrapped in standard Laravel collections or paginators, completely preventing the presentation layer from triggering Eloquent persistence mutations or un-scoped queries.
3. **Domain Filtering Ownership**: `Event` enforces 100% of business publication rules (`status`, availability date windows, and seat thresholds). `Website` never performs business visibility queries.
4. **Presentation Extraction**: Main-site routes, presentation controllers, Blade views, and navigation registrations are designated to move to `Website` in future authorized implementation steps, eliminating `Event`'s Composer dependency on `webkul/web`.
5. **No Code Modified**: This step is strictly architectural and design-focused. No files were moved or changed.

---

## 2. Source Evidence Reviewed

The following files within [`packages/Webkul/Event`](file:///home/hosam/Documents/CampusHub-main/packages/Webkul/Event) were forensically inspected:

- [`packages/Webkul/Event/src/Models/Event.php`](file:///home/hosam/Documents/CampusHub-main/packages/Webkul/Event/src/Models/Event.php):
  - Model attributes: `id`, `title`, `event_date`, `event_end_date`, `organizer`, `available_seats`, `availability_use_seats`, `availability_use_end_date`, `image`, `description`, `status`.
  - Scopes: `scopePublished($query)` enforces `status = 1`, seat availability rules, and unexpired end date rules.
  - Domain availability method: `isCurrentlyAvailable(): bool`.
  - Relations: `categories()`, `fields()`, `images()`, `subscribers()`.
- [`packages/Webkul/Event/src/Repositories/EventRepository.php`](file:///home/hosam/Documents/CampusHub-main/packages/Webkul/Event/src/Repositories/EventRepository.php):
  - `paginatePublic()`: Applies `published()` scope, selects specific public columns, orders by date, paginates.
  - `findPublicOrFail(int $id)`: Applies `published()` scope, eager loads `images:id,event_id,path,position`.
  - `availableForSubscription()`: Bounded query for active upcoming events.
- [`packages/Webkul/Event/src/Http/Controllers/Web/EventController.php`](file:///home/hosam/Documents/CampusHub-main/packages/Webkul/Event/src/Http/Controllers/Web/EventController.php):
  - `index()`: Calls `EventRepository->paginatePublic()`, sets SEO metadata, returns `event::web.index`.
  - `show(int $event)`: Calls `EventRepository->findPublicOrFail($event)`, sets SEO metadata, returns `event::web.show`.
- [`packages/Webkul/Event/src/Resources/views/web/index.blade.php`](file:///home/hosam/Documents/CampusHub-main/packages/Webkul/Event/src/Resources/views/web/index.blade.php):
  - Renders event cards using `<x-web::card>`, `<x-web::badge>`, `<x-web::button>`.
  - Accesses `$event->image`, `$event->title`, `$event->event_date`, `$event->organizer`, `$event->description`, and `$event->getKey()`.
  - Renders pagination controls.
- [`packages/Webkul/Event/src/Resources/views/web/show.blade.php`](file:///home/hosam/Documents/CampusHub-main/packages/Webkul/Event/src/Resources/views/web/show.blade.php):
  - Renders detail page: `$event->title`, `$event->images` (paths), `$event->image`, dates, organizer, seats, description.
- [`packages/Webkul/Event/src/Routes/web-routes.php`](file:///home/hosam/Documents/CampusHub-main/packages/Webkul/Event/src/Routes/web-routes.php):
  - Declares `GET events` and `GET events/{event}` with `whereNumber('event')`.
- [`packages/Webkul/Event/src/Providers/EventServiceProvider.php`](file:///home/hosam/Documents/CampusHub-main/packages/Webkul/Event/src/Providers/EventServiceProvider.php):
  - Injects `event.events` into `NavigationRegistryContract`.

---

## 3. Current Event Public Data Requirements

From physical view inspection, the entire current public web surface of Event consumes exactly the following data:

1. **Card/Listing View (`web/index.blade.php`)**:
   - `id`: integer primary key (used in `route('event.web.show', $event->getKey())`).
   - `title`: string.
   - `image`: string path (passed to `Storage::disk('public')->url()`).
   - `event_date`: Carbon date instance formatted as `Y-m-d`.
   - `organizer`: string or null.
   - `description`: text truncated to 220 characters.
   - Pagination metadata: total, per_page, current_page, last_page.
2. **Detail View (`web/show.blade.php`)**:
   - `id`: integer primary key.
   - `title`: string.
   - `description`: full text or null.
   - `image`: fallback primary image path.
   - `images`: list of gallery image paths (`path`).
   - `event_date`: Carbon date instance.
   - `event_end_date`: Carbon date instance or null.
   - `organizer`: string or null.
   - `available_seats`: integer or null.
   - `availability_use_seats`: boolean (true = limited seats, false = unlimited).
3. **Homepage/Section Needs (Projected from `SectionRegistryContract`)**:
   - Bounded list of upcoming events (3 to 5 items) with title, date, primary image, and public identifier.

Zero other fields or relations are required by public visitors.

---

## 4. Domain vs Query vs Presentation Classification

Every responsibility identified in current code is classified as follows:

| Component / Logic | Classification | Current Owner | Target Owner | Reason |
|---|---|---|---|---|
| `status = 1` check | `DOMAIN_RULE` | `Event` model scope | `Event` | Domain rule defining public status. |
| End date / seats validity | `DOMAIN_RULE` | `Event::isCurrentlyAvailable()` | `Event` | Business logic defining record validity. |
| Public events query & ordering | `PUBLIC_QUERY` | `EventRepository` | `Event` | Data retrieval applying domain scopes. |
| Public event fields & attributes | `PUBLIC_DATA` | `Event` model | `Event` (via DTO) | Business information exposed across contract. |
| Event card grid layout | `PRESENTATION_DECISION` | `web/index.blade.php` | `Website` | Visual presentation of cards. |
| Placing events on Homepage | `SITE_COMPOSITION` | Not yet implemented | `Website` | Site decides whether home features events. |
| Placing events in Navigation | `SITE_COMPOSITION` | `EventServiceProvider` | `Website` | Site decides menu hierarchy. |
| SEO `<title>` and OpenGraph format | `PRESENTATION_DECISION` | `Web/EventController` | `Website` | Presentation metadata formatting. |

---

## 5. Proposed `EventPublicReadContract`

The recommended contract boundary, owned by `Webkul\Event`, is derived strictly from existing query requirements:

```php
namespace Webkul\Event\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Webkul\Event\DataTransferObjects\PublicEventData;

interface EventPublicReadContract
{
    /**
     * Return a paginated list of publicly visible, active events.
     *
     * @param  int|null  $perPage
     * @return LengthAwarePaginator<PublicEventData>
     */
    public function paginatePublicEvents(?int $perPage = null): LengthAwarePaginator;

    /**
     * Find a single publicly visible event by its public identifier.
     * Returns null if not found, inactive, or unavailable.
     *
     * @param  int|string  $identifier
     * @return PublicEventData|null
     */
    public function findPublicEvent(int|string $identifier): ?PublicEventData;

    /**
     * Get a collection of upcoming publicly visible events.
     *
     * @param  int  $limit
     * @return Collection<int, PublicEventData>
     */
    public function getUpcomingEvents(int $limit = 5): Collection;
}
```

### Methods Omitted & Rationale:
- **No speculative filtering methods**: No category filtering, tags, full-text search, or date range arguments are added because none exist in current public views.
- **No write operations**: No create, update, delete, or registration methods exist on this contract.

---

## 6. Return Type Decision

### Comparison of Options

| Return Type | Coupling | Mutation Safety | Lazy Loading Risk | Testability | Recommendation |
|---|---|---|---|---|---|
| **Eloquent `Event` Model** | High (couples to DB columns) | Dangerous (`$event->delete()` possible) | High (accidental N+1 in views) | Requires DB | **REJECTED** |
| **Array `array<string, mixed>`** | None | Safe | None | Easy | Weak typing / unstructured |
| **Read Model DTO (`PublicEventData`)** | **None** | **Completely Immutable** | **Zero N+1 risk** | **100% Mockable** | **RECOMMENDED** |

### Decision: Read Model DTO (`PublicEventData`)
The contract returns `PublicEventData` objects.  
This guarantees that `Website`:
1. Cannot mutate Event state from Blade templates.
2. Cannot trigger hidden database queries or lazy-load private relations.
3. Can test its controllers, views, and presenters in complete isolation without running migrations or database seeders.

---

## 7. Public Data Shape

Specification for `Webkul\Event\DataTransferObjects\PublicEventData`:

```php
namespace Webkul\Event\DataTransferObjects;

use Carbon\CarbonInterface;

final readonly class PublicEventData
{
    /**
     * @param  list<string>  $galleryImages
     * @param  list<string>  $categories
     */
    public function __construct(
        public int $id,
        public string $title,
        public ?string $description,
        public ?string $summary,
        public ?CarbonInterface $startDate,
        public ?CarbonInterface $endDate,
        public ?string $organizer,
        public ?string $primaryImage,
        public array $galleryImages,
        public ?int $availableSeats,
        public bool $hasSeatLimit,
        public array $categories = [],
    ) {}
}
```

### Field Classification Audit

| Field | Classification | Exposed in DTO? | Reason |
|---|---|---|---|
| `id` | `PUBLIC_REQUIRED` | YES | Route parameter for `/events/{id}`. |
| `title` | `PUBLIC_REQUIRED` | YES | Displayed on cards, page titles, SEO. |
| `description` | `PUBLIC_REQUIRED` | YES | Full description on detail page. |
| `summary` | `PUBLIC_REQUIRED` | YES | Excerpt for index card and meta tags. |
| `event_date` (`startDate`) | `PUBLIC_REQUIRED` | YES | Schedule indicator on listing and detail. |
| `event_end_date` (`endDate`) | `PUBLIC_REQUIRED` | YES | Displayed on detail page. |
| `organizer` | `PUBLIC_REQUIRED` | YES | Organizer attribution on listing and detail. |
| `image` (`primaryImage`) | `PUBLIC_REQUIRED` | YES | Primary thumbnail image. |
| `images` (`galleryImages`) | `PUBLIC_REQUIRED` | YES | Gallery images for show page. |
| `available_seats` | `PUBLIC_REQUIRED` | YES | Remaining seats count. |
| `availability_use_seats` | `PUBLIC_REQUIRED` | YES | Determines if seats are limited vs unlimited. |
| `categories` | `PUBLIC_OPTIONAL` | YES | Category labels if needed by presentation. |
| `status` | `INTERNAL_ONLY` | **NO** | Enforced by query; never exposed to consumer. |
| `subscribers` / `students` | `SENSITIVE` | **NO** | Student PII; strictly forbidden in public DTO. |
| `custom_fields` | `NOT_CURRENTLY_NEEDED` | **NO** | Internal CRM/Admin fields. |

---

## 8. Identifier Decision

- **Current Repository State**: The `events` table schema contains only `id` (integer auto-increment). There are zero `slug` or `uuid` columns in `packages/Webkul/Event`.
- **Finding**:
  ```text
  MISSING_PUBLIC_IDENTIFIER
  ```
- **Architectural Policy**:
  - Do NOT invent or add a `slug` migration in this step.
  - The current contract accepts `int|string $identifier` so that integer `id` works today without breaking if slugs are introduced in a future authorized domain migration.
  - Public URLs will currently use `/events/{id}` with `whereNumber('id')`.

---

## 9. Domain Filtering Ownership

Website MUST NOT filter events by business rules.

```text
CORRECT:
Website calls: $eventReader->paginatePublicEvents()
Event executes: Event::query()->published()->...

FORBIDDEN:
Website calls: Event::where('status', 1)->where('event_date', '>=', now())->get()
```

All invariants governing whether an event is publicly accessible remain strictly encapsulated in `Event`:
1. `status == 1`.
2. Seats available (`availability_use_seats == false` OR `available_seats > 0`).
3. Not expired (`availability_use_end_date == false` OR `event_end_date >= today`).
4. Deleted records excluded.

If an event with `status = 0` is requested by ID, `EventPublicReadContract::findPublicEvent()` returns `null`, and Website yields a standard 404 response.

---

## 10. Contract Ownership

The contract interface is owned by:
```text
Webkul\Event\Contracts\EventPublicReadContract
```
Located in: `packages/Webkul/Event/src/Contracts/EventPublicReadContract.php`.

**Compliance with Repository Rules**:
- Rule 08 (Section 2 & 3): Packages own their public integration surfaces; Foundation and consumers do not own domain contracts.
- Rule 09 (Section 19): Deliberate public APIs belong to the owning package as stable interfaces.

---

## 11. Implementation Ownership

### Options Evaluated:
1. **Existing `EventRepository`**: Already possesses `paginatePublic()` and `findPublicOrFail()`.
2. **Dedicated `EventPublicReadService`**: Separate service class in `Webkul\Event\Services`.

### Recommendation:
`EventRepository` implements `EventPublicReadContract` directly (or delegates via `EventServiceProvider` binding).  
Because `Website` typehints `EventPublicReadContract`, `Website` is restricted to the three public read methods and cannot call repository write methods (`create`, `update`, `delete`). This avoids unnecessary class bloat while maintaining strict interface segregation.

---

## 12. Website Consumption Model

Within `Webkul\Website`:

```text
Website Routes (web-routes.php)
       │
       ▼
Website Controller (EventPageController)
       │
       ▼ [Injects EventPublicReadContract]
Public Read Contract (Webkul\Event\Contracts\EventPublicReadContract)
       │
       ▼
Event Implementation (Webkul\Event\Repositories\EventRepository)
       │
       ▼ [Returns PublicEventData DTOs]
Website Controller
       │
       ▼ [Formats SEO, layout, view data]
Website Blade View (website::events.index / website::events.show)
```

Website owns:
- URL structure and routes (`/events`, `/events/{id}`).
- Controller invocation and HTTP response codes (404 handling).
- Blade views and component usage (`<x-web::card>`, `<x-web::badge>`).
- SEO tags via `SeoMetadataContract`.
- Navigation registration in `NavigationRegistryContract`.
- Homepage section registration in `SectionRegistryContract`.

---

## 13. Optional Dependency Model

A site package requiring events (e.g. `UniversityWebsite`) declares the dependency in `composer.json`:

```json
{
    "name": "webkul/website",
    "require": {
        "webkul/web": "dev-main",
        "webkul/event": "dev-main"
    },
    "extra": {
        "campushub": {
            "id": "website",
            "type": "optional",
            "provider": "Webkul\\Website\\Providers\\WebsiteServiceProvider"
        }
    }
}
```

### Enforcement Mechanism:
- `OptionalPackageManifestLoader` parses `composer.json` requirements and discovers that `website` requires `event`.
- `OptionalPackageComposition` validates at boot that `event` is present in `CAMPUSHUB_OPTIONAL_PACKAGES`.
- If `website` is enabled while `event` is disabled, boot fails fast:
  ```text
  InvalidPackageComposition: Optional package "website" requires enabled package "event".
  ```
- **Zero scattered `class_exists()` or runtime reflection checks are allowed.**

---

## 14. Read Contracts vs Domain Events

| Dimension | Read Contract (`EventPublicReadContract`) | Domain Event (`EventPublished`) |
|---|---|---|
| **Paradigm** | **PULL** (Request-driven query) | **PUSH** (Occurrence announcement) |
| **Direction** | `Website -> Event` (Synchronous) | `Event -> Dispatcher` (Decoupled) |
| **Payload** | Paginated collections / Event DTOs | Event ID, timestamp, minimal context |
| **Usage** | Serving `/events` pages or homepage widgets | Invalidation of static caches, audit logging |
| **Substitutability** | Cannot be replaced by events | Cannot be replaced by queries |

---

## 15. Website Event Listener Ownership

If `Website` maintains a cached homepage or pre-rendered event catalog:
1. `Event` publishes domain event `Webkul\Event\Events\EventPublished`.
2. `Website` defines `Webkul\Website\Listeners\ClearWebsiteEventCache`.
3. `WebsiteServiceProvider` binds the listener to the event.
4. If `Website` is uninstalled/disabled:
   - The listener is automatically removed.
   - `Event` continues publishing without knowledge or error.

---

## 16. Website Event Presentation Structure

Recommended non-ceremonial layout inside `packages/Webkul/Website/`:

```text
packages/Webkul/Website/src/
├── Http/
│   └── Controllers/
│       ├── HomeController.php
│       └── EventPageController.php
├── Providers/
│   └── WebsiteServiceProvider.php
├── Resources/
│   └── views/
│       ├── events/
│       │   ├── index.blade.php
│       │   └── show.blade.php
│       └── sections/
│           └── upcoming-events.blade.php
└── Routes/
    └── web-routes.php
```

*No unnecessary `Adapters/`, `Ports/`, or `Gateways/` folders.*

---

## 17. Route Resolution Strategy

- **Implicit Model Binding**: **PROHIBITED**.
  - Binding `{event}` directly to `Webkul\Event\Models\Event` bypasses `published()` scope security checks, coupling presentation routes directly to database entities.
- **Contract-Mediated Resolution**: **MANDATORY**.
  - Route: `Route::get('events/{id}', [EventPageController::class, 'show'])->whereNumber('id');`
  - Controller invokes: `$this->eventReader->findPublicEvent($id);`
  - If null, controller throws `NotFoundHttpException` (404).

---

## 18. SEO Boundary

- **`Event` Responsibilities**: Provides raw title, description, summary, and primary image path via `PublicEventData`.
- **`Website` Responsibilities**:
  - Injects `SeoMetadataContract`.
  - Sets site-branded title: `$seo->setTitle($event->title . ' | ' . config('app.name'))`.
  - Sets meta description: `$seo->setDescription($event->summary)`.
  - Sets canonical URL: `$seo->setCanonicalUrl(route('website.events.show', $event->id))`.
  - Configures OpenGraph / Twitter cards with site branding.

---

## 19. Navigation Boundary

- **`Event` Responsibilities**: **ZERO main-site navigation knowledge**. `EventServiceProvider` must not touch `NavigationRegistryContract`.
- **`Website` Responsibilities**:
  - Registers navigation items:
    ```php
    $navigation->register([
        'id' => 'website.events',
        'title' => NavigationLabel::translation('website::app.navigation.events'),
        'url' => '/events',
        'order' => 20,
        'location' => 'header',
    ]);
    ```

---

## 20. Homepage Composition Boundary

- **`Event` Responsibilities**: Provides data via `$eventReader->getUpcomingEvents(3)`.
- **`Website` Responsibilities**:
  - Contributes section into `SectionRegistryContract`:
    ```php
    $sectionRegistry->register([
        'page' => 'home',
        'key' => 'upcoming_events',
        'view' => 'website::sections.upcoming-events',
        'order' => 30,
        'data' => fn () => [
            'events' => app(EventPublicReadContract::class)->getUpcomingEvents(3),
        ],
    ]);
    ```

---

## 21. Theme Boundary

Themes (`themes/base` or site themes) define styling only:
- Theme templates and Blade overrides MUST NEVER inject or query `EventPublicReadContract`.
- All data must be passed down from Website controllers or Section data closures into the views.
- CSS classes restyle cards; Blade overrides customize HTML structure without performing queries.

---

## 22. Future Verification Matrix

The following test suites must be developed when implementation begins:

| Test Case | State | Assertions |
|---|---|---|
| **Isolation Test** | Foundation + Event (`Website` Absent) | 1. `GET /events` returns 404.<br>2. `GET /events/1` returns 404.<br>3. `NavigationRegistry` has 0 event items.<br>4. Root `/` displays default Web landing page.<br>5. `/admin/events` returns 200 OK. |
| **Contract Test** | Foundation + Event | 1. `EventPublicReadContract` resolves from container.<br>2. `paginatePublicEvents()` returns `LengthAwarePaginator<PublicEventData>`.<br>3. Unpublished events (`status = 0`) are excluded.<br>4. Expired events are excluded.<br>5. `findPublicEvent($id)` returns null for invalid/unpublished events. |
| **Composition Test** | Foundation + Event + Website | 1. `GET /events` returns 200 OK.<br>2. `GET /events/1` returns 200 OK.<br>3. Header navigation contains Events link.<br>4. Root `/` contains upcoming events section. |
| **Failure Test** | Website Enabled, Event Disabled | Boot throws `InvalidPackageComposition` at config load time. |

---

## 23. Current Code Migration Map

Action plan for existing artifacts in subsequent implementation steps:

| Existing Artifact | Classification | Target Location / Migration Strategy |
|---|---|---|
| `packages/Webkul/Event/src/Routes/web-routes.php` | `MOVE_TO_WEBSITE` | Relocate to `packages/Webkul/Website/src/Routes/web-routes.php`. |
| `packages/Webkul/Event/src/Http/Controllers/Web/EventController.php` | `MOVE_TO_WEBSITE` | Relocate to `packages/Webkul/Website/src/Http/Controllers/EventPageController.php`, refactored to consume contract. |
| `packages/Webkul/Event/src/Resources/views/web/*` | `MOVE_TO_WEBSITE` | Relocate to `packages/Webkul/Website/src/Resources/views/events/*`. |
| `EventServiceProvider::registerWebNavigation()` | `REMOVE_AFTER_EXTRACTION` | Delete method; navigation will be registered by `WebsiteServiceProvider`. |
| `EventRepository::paginatePublic()` & `findPublicOrFail()` | `REPLACE_WITH_CONTRACT` | Bind to `EventPublicReadContract` returning `PublicEventData`. |
| `packages/Webkul/Event/src/Resources/lang/*/web.php` | `NEEDS_SPLIT` | Move UI strings ("View Details", "Back") to `Website`; keep domain-specific labels in `Event`. |
| `packages/Webkul/Event/composer.json` (`webkul/web` requirement) | `REMOVE_AFTER_EXTRACTION` | Remove `"webkul/web"` dependency from `Event` manifest once presentation code is relocated. |

---

## 24. Zero-Data-Loss Invariants

Future presentation extraction must strictly uphold:
1. **Schema Immutability**: No tables or columns in `Event` are dropped, renamed, or altered during presentation relocation.
2. **Data Preservation**: Zero event records, categories, images, or student subscriptions are modified.
3. **Admin Continuity**: All Admin routes (`/admin/events/*`), DataGrids, and ACL permissions remain completely unaffected.

---

## 25. Proposed Permanent Laws

- **SITE-01**: `Website` consumes Business Packages exclusively through explicit package-owned public contracts.
- **SITE-02**: `Website` MUST NOT import Business Package repositories or Eloquent models directly.
- **SITE-03**: Business Packages own and enforce public visibility and domain invariants.
- **SITE-04**: `Website` owns main-site public presentation decisions (routing, navigation, layouts, views, SEO, components).
- **SITE-05**: Themes MUST NEVER perform business queries or resolve business contracts.
- **SITE-06**: `Website` removal removes presentation only, never business capability or database records.
- **SITE-07**: Business Package main-site UI MUST NOT exist merely because the Business Package is enabled.

---

## 26. Risks & Mitigations

1. **Risk: Missing Slug / Opaque Public Identifier**:
   - *Detail*: Public routes currently use auto-increment IDs (`/events/1`).
   - *Mitigation*: The contract accepts `int|string $identifier` today, allowing future slug introduction without breaking the contract signature.
2. **Risk: Data Leakage in DTOs**:
   - *Detail*: Accidental inclusion of student attendee lists or draft event details.
   - *Mitigation*: The `PublicEventData` DTO explicitly defines typed constructor arguments, excluding student relations and internal fields.

---

## 27. Blockers

- **ZERO blockers identified.** The current domain scopes and repository read queries in `Event` map cleanly into the proposed `EventPublicReadContract`.

---

## 28. Recommended Step 03

**Recommended Next Step: STEP 03 — Event Public Capability Contract Implementation**.  
In Step 03:
1. Implement `Webkul\Event\Contracts\EventPublicReadContract` and `Webkul\Event\DataTransferObjects\PublicEventData` in `Webkul\Event`.
2. Bind the contract to `EventRepository` in `EventServiceProvider`.
3. Add unit and feature tests verifying that `EventPublicReadContract` enforces visibility rules and returns immutable DTOs without touching Web presentation.

---
