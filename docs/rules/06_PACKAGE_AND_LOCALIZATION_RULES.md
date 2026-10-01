# Laraseed Package Ownership and Localization Rules

> **Repository-wide authority.** Any future AI agent or developer creating or modifying a Laraseed package MUST comply with this rule. Package creation is blocked until localization classification is complete. A passing test suite does not justify violating localization architecture. Do not modify tests merely to permit an architecture violation.

This rule complements [the rules index](./README.md) and the [LostAndFound domain rules](./LOST_AND_FOUND_PACKAGE_RULES.md). Repository-wide instructions take precedence; the existing package-specific security and domain invariants remain in force. These rules describe architecture, not permission to add migrations, a Locale model, or localized LostAndFound storage.

## 1. Two distinct localization layers

| Layer | Owns | Storage |
| --- | --- | --- |
| Static/UI | Page titles, actions, labels, validation/flash/error text, DataGrid headings, ACL/menu/configuration labels, emails and notifications | Each package's `src/Resources/lang/<locale>/app.php`, loaded under that package's translation namespace |
| Domain/data | Administrator-authored multilingual master data or public content whose value legitimately varies by language | Model-owned, locale-qualified database content after an explicit schema design |

Language files do **not** hold business records. A database Locale being enabled does **not** guarantee a package has a complete static translation for it; a package language file does **not** mean a business record has a translation. Fallback output must never be represented as an actual translation.

## 2. Verified pre-Step-01 Laraseed baseline (2026-09-28)

- `config/app.php` defines `locale = env('APP_LOCALE', 'en')`, `fallback_locale = 'en'`, and a fixed `available_locales` map of `ar` and `en`. `Webkul\Core\Core::locales()` reads that map for the Admin selector. The seven language directories currently shipped by several packages (`ar`, `en`, `es`, `fa`, `pt_BR`, `tr`, `vi`) are **static-resource coverage**, not seven dynamically enabled locales.
- `Webkul\Admin\Http\Middleware\Locale` reads `general.general.locale_settings.locale` through `core()->getConfigData()` and otherwise keeps the app locale. The Admin configuration field has default `en`; `SystemConfig::getConfigData()` checks `core_config` first, then the configured default. The inspected runtime database stores `ar` for that setting. This is a site-wide Admin setting, not a per-user locale preference.
- Student routes and Shop web routes use `admin_locale`; LostAndFound student routes also use it. There is no verified Student-specific selection or session locale flow. Public Shop pages use the same middleware rather than Bagisto's channel locale middleware.
- Only Installer middleware currently reads a `locale` query parameter and stores `installer_locale` in the session. This is Installer behavior, not a general Admin/Student/Public locale contract. Inbound `routes/api.php` and Shop `Routes/api.php` define no locale-aware application endpoints.
- Admin, Shop, and Installer layouts emit `<html lang>` and `<html dir>` from the app locale, using hardcoded `ar`/`fa` (Installer also `he`) RTL lists. This is partial presentation support; the new registry direction metadata is not yet wired into those layouts. Student pages are rendered within a Shop layout.
- Before Step 01 there was no Laraseed `Locale` model, contract, proxy, repository, or `locales` table. The inspected runtime database has not been migrated to the new schema. There is still no channel/site locale relation or dynamic language-management UI.
- Shop's 2025 theme-customization translation migration is historical: `2025_03_23_130000_consolidate_shop_theme_options.php` moves options into the base table and drops the translation table on upgrade. It is not a general model-translation API or a reason to copy its design.

These are **pre-foundation and still-relevant integration** facts, not a claim that request selection or model translations use the new registry. Relevant sources: `config/app.php`, `packages/Webkul/Core/src/Core.php`, `packages/Webkul/Core/src/SystemConfig.php`, `packages/Webkul/Admin/src/Config/core_config.php`, `packages/Webkul/Admin/src/Http/Middleware/Locale.php`, `packages/Webkul/Student/src/Providers/StudentServiceProvider.php`, `packages/Webkul/Shop/src/Providers/ShopServiceProvider.php`, `packages/Webkul/Installer/src/Http/Middleware/Locale.php`, and the Shop migrations above.

## 3. Independent package ownership

Every independent feature package owns its feature-specific controllers, routes, ACL/menu definitions when applicable, DataGrids, views, translation namespace and files, models, repositories, migrations, application/domain services, and tests. It may consume Admin/User/Student/Core/DataGrid infrastructure without placing feature behavior or translations in those packages. Its provider registers its own translation namespace with `loadTranslationsFrom()`, and merges package ACL/config through the verified host extension mechanism. Host registration (autoload, provider, Concord when needed, shared storage/middleware) remains an explicit integration point. Do not introduce a dependency on Admin or Shop into a package's domain layer.

Static user-facing strings MUST use package keys, including success/errors, validation, ACL, menus, DataGrids, emails, and empty states. Use `en` as the reference key structure because this repository has no competing canonical convention. Preserve equivalent keys in every locale the package currently ships; the existing LostAndFound rule specifically requires parity across its seven shipped locales. Do not assume the two presently selectable locales are the only locales that may ever be enabled. Missing current-locale UI keys may fall back only through configured `app.fallback_locale`; missing fallback keys are defects, not acceptable raw UI output.

## 4. Locale foundation contract — Step 01 registry implemented

The global `Webkul\Core` Locale domain now owns the persistent registry. Its `locales` table has `id`, unique `code`, `name`, `direction`, `is_active`, deterministic `sort_order`, and timestamps. Code normalization accepts a two- or three-letter language plus an optional two-letter or three-digit region, separated by `_` or `-` on input. Stored form is lowercase language plus uppercase region (`PT-br` → `pt_BR`); persisted codes are immutable. Direction is `ltr` or `rtl` through `LocaleDirection`. Locale identities are retained: deactivate rather than delete. Do not add parallel package-specific Locale tables, speculative logo/channel fields, or a second default/fallback flag.

The migration seeds a fixed snapshot of the two currently selectable codes, `ar` and `en`, with direction and order. It does not read mutable configuration during migration, and it does not activate all seven shipped static language folders. `LocaleRepository` is the new persistent identity/active-order/direction lookup API; it does not select a request locale. `Core::locales()` deliberately continues to read `config('app.available_locales')` for the Admin selector and Installer compatibility until a later integration step. No provider boot path reads `locales`, so application boot before migration remains safe. Feature packages must consume the central registry as later integration is added, not duplicate locale arrays.

Step 02 separates two authorities: the Admin interface locale remains `general.general.locale_settings.locale` in `core_config` (with its existing `Core::locales()` options); the global website/content locale registry is `locales`, with the sole primary in `content_locale_settings`. The primary bootstraps to active `en` independently of Admin configuration and `APP_LOCALE`. The application/CLI static-language fallback remains `config('app.fallback_locale')`, **not** an additional content-primary or model-data fallback. A database locale may be active without static translation coverage, and static coverage never activates a database locale. Future package translation tables should reference `locales.id` with a foreign key and unique `(entity_id, locale_id)`: the ID gives compact joins and referential integrity while the stable code remains the external identifier. Persisted codes are immutable; locale hard deletion is forbidden in normal workflows, including for unused entries. Any exceptional removal or code migration needs separate architectural approval and data migration.

`ContentLocaleService` is the mutation boundary. Exactly one primary setting must exist and its target must be active. Changing primary activates an inactive target and switches the pointer in one transaction; the former primary remains active. Primary deactivation and deletion, last-active deactivation and deletion, and direct model/repository activation edits are forbidden. Other active locales may be deactivated without deleting their identity or eventual translation rows. New locales start inactive in the Admin management UI; activation does not create content or static translations. Before future translation tables exist, the service is the extension point for primary-switch content-readiness validation; Step 02 does not pretend translations exist.

A content-write locale MUST be selected explicitly from active content locales; it MUST NOT be inferred from the Admin UI locale. A locale becoming inactive retains its identity and existing translation rows, which simply cease to be selectable for new content writes. Adding or activating a content locale neither translates Admin UI strings nor creates missing package static resources. Machine identifiers remain independent of language.

Laraseed has no verified Channel equivalent or separate campus/site locale administration. The content language set is global and ordered by `LocaleRepository::activeOrdered()` through `ContentLocaleService::activeContentLocales()`; future content forms must use this set and explicitly record the selected locale. Propose surface-specific availability only after a concrete requirement. Do not silently choose the first locale in a collection. Static `app.fallback_locale` need not equal a database content locale.

Current selection and intended future resolution are distinct:

| Surface | Current verified behavior | Future contract, contingent on foundation/UI implementation |
| --- | --- | --- |
| Admin | `admin_locale` uses stored `core_config` setting, else current app locale; field default `en` | Validated explicit selection, then implemented user/session preference, then Admin/global default, then configured fallback |
| Student | Student and package routes use `admin_locale`; this is legacy request presentation, not content-primary authority | Future validated explicit selection, then implemented student/session preference, then primary content locale; do not pretend preference exists now |
| Public | Shop `shop` middleware aliases `admin_locale`; no public selector was found | Future validated explicit selection, then implemented session preference, then primary content locale; no Channel assumption |
| API | No verified inbound locale contract | Each API must define and validate an explicit locale mechanism (header or parameter) before adding localized responses; absent locale uses documented global default/fallback |
| CLI/jobs | Starts from configured app locale; no browser session | Pass an explicit locale for localized work, or resolve the authoritative global default and fallback |
| Installer | Query `locale` then `installer_locale` session then app locale | Keep isolated; validate against enabled codes if/when the foundation covers Installer |

Never accept arbitrary unsupported or disabled codes as an active content locale. The chosen direction must drive `<html lang>` and `<html dir>` (or API metadata) through authoritative locale metadata; feature packages must use direction-aware styling and must not hardcode LTR assumptions. Existing hardcoded RTL checks are a **foundation gap**, not proof of complete RTL architecture.

## 5. Model and field classification gate

Before creating **or materially extending** a model/migration, document whether it is `NON_TRANSLATABLE`, `PARTIALLY_TRANSLATABLE`, or `FULLY_TRANSLATABLE_CONTENT`. Classify every user-visible field as `INVARIANT`, `LOCALE_DEPENDENT`, or `OPTIONALLY_LOCALE_DEPENDENT`. Ask: does it hold user-visible business content, and must that content vary by locale? If yes, establish its locale storage/read/write/fallback design **before** the migration. A passing test suite does not waive this gate.

Identity and machine state remain invariant: IDs, codes, public references, route names, ACL keys, enum values, foreign keys, storage keys, workflow statuses, and timestamps. Localize the displayed label of a fixed enum using package UI translations; do not translate its persisted value. Free-text reports, claim/evidence statements, staff notes, verification details, and other one-language accounts of an event are **not automatically multilingual master data**. Preserve their original language and access controls. Only deliberate editorial multilingual content is a candidate for translation rows.

Language-suffix columns (`name_en`, `name_ar`, `description_fr`, etc.) are forbidden for ordinary extensible multilingual design. Adding a locale must not normally require a migration. An exceptional fixed-schema requirement needs explicit documented architectural approval before implementation.

## 6. Future database translation pattern

For ordinary entities with a small, known set of localized fields, prefer a **package-owned translation table**: invariant parent row plus child rows carrying parent key, `locale_id` referencing the global `locales` table, and translated fields. Enforce unique `(parent_id, locale_id)`; resolve and validate external locale codes through the authoritative Locale registry. Determine parent deletion behavior from the entity lifecycle; never assume cascade deletion. Decide unique translated slugs/names with an explicit domain scope such as `(locale_id, slug)` only where required. A translated URL slug must be explicitly classified as variant or invariant; do not mix semantics.

Creation normally requires a **primary content locale** translation, not every enabled locale. Writes must explicitly identify entity, validated active content locale, and translated fields; editing `ar` must never overwrite `en`. Keep invariant-field updates separate and authorized. Forms must expose the content locale through a selector/tabs driven by `ContentLocaleService::activeContentLocales()`, not one field per hardcoded language. Primary switches may need a model-specific readiness gate once translatable content exists.

The future foundation should expose one centralized model/content resolver or read-model contract. The conceptual operation is `resolve(entity, requestedLocale)` returning **value**, **requested locale**, **resolved locale**, and **has requested translation**; the concrete API must be selected after inspecting the implementing models/repositories. Presentation code must not scatter `translations()->where('locale', app()->getLocale())` lookups or silently rely on an ambiguous `$model->name` accessor. Eager-load or join in bulk reads to avoid N+1 queries.

Future missing model-data policy: **requested active content locale → primary content locale → `null`/explicit unavailable state**, deduplicating equal codes. Static UI fallback via `app.fallback_locale` is a separate translation-file mechanism, never a third model-data fallback. Never pick an arbitrary first translation. The response/UI must distinguish primary fallback content from a true requested-locale translation. If the primary row is missing, surface that defect rather than inventing content.

Localized searches and DataGrid sorts must query the intended locale's rows deterministically, with the same documented fallback semantics and bounded joins; never search/sort against an arbitrary translation. Static DataGrid column titles continue to use package language keys. Any cache of localized values must include locale and relevant fallback/default policy in its identity or invalidate safely when those settings change. Jobs must not inherit a browser locale. Validation messages use static package keys, while translated-field validation applies to the specific locale being edited.

This section is a **design requirement**, not authorization to create a Locale model/table, translation table, migration, or shared library in this audit step.

## 7. Required package design record

Complete this before package implementation or a new content model:

```text
PACKAGE NAME / TYPE: independent feature | infrastructure
SURFACES: Admin / Student / Public / API (yes or no each)
OWNER OF ROUTES, ACL, MENU, VIEWS, SERVICES, PERSISTENCE:
TRANSLATION NAMESPACE / SHIPPED STATIC LOCALES / REFERENCE LOCALE:
STATIC UI LOCALIZATION / RTL-SAFE PRESENTATION:
MODEL LIST: non-translatable / partially translatable / fully translatable
FIELD MAP: invariant / locale-dependent / optionally locale-dependent
AUTHORITATIVE ENABLED-LOCALE SOURCE / DEFAULT / FALLBACK:
CONTENT READ API / MISSING-TRANSLATION POLICY / COMPLETENESS SIGNAL:
EXPLICIT LOCALE WRITE CONTRACT / CROSS-LOCALE ISOLATION:
LOCALE-AWARE SEARCH / SORT / CACHE / API / JOB REQUIREMENTS:
REQUIRED MIGRATIONS (after approval) / BACKFILL / LIFECYCLE POLICY:
TEST STRATEGY:
```

No feature package is complete without verified ownership, provider/translation integration, applicable routes/ACL, static localization and parity, RTL-safe presentation, model classification, and tests.

## 8. Required tests and prohibited shortcuts

Static localization tests must verify namespace loading, `en` reference structure, parity across shipped locales (missing **and orphan** keys), no raw keys, and resolution of ACL, menu when present, DataGrid, validation, and flash labels. Test RTL metadata and `<html lang>/<html dir>` once the Locale foundation owns direction.

Any translatable model must test default-locale creation, second-locale creation, requested-locale read, locale switching with stable entity identity, isolated update, exact fallback and completeness flag, duplicate parent+locale rejection, invariant machine fields, disabled/unsupported locale rejection, and parent lifecycle. Test locale-scoped search/sort and cache isolation wherever those features exist. Test background/API locale contracts when they produce localized data.

Forbidden: hardcoded user-facing strings; feature translations in Admin/Core/Student solely because of presentation surface; hardcoded locale arrays inside feature packages; language-suffix columns; business data in `Resources/lang`; translated machine identifiers; scattered manual locale queries; arbitrary translation fallback; treating fallback as completeness; cross-locale overwrite; assuming browser session locale in jobs; and altering tests to excuse an architectural violation.

## 9. Current LostAndFound classification (audit only)

`LostFoundCategory` currently contains only invariant `id`, `code`, `is_active`, `sort_order`, and timestamps. A future administrator-authored category name/description is strong **partially translatable master-data** candidate; the fields and translation table do not exist today. No category migration is authorized by this rule.

`FoundItem` title, public description, and found-location text are incident-specific staff-entered observations, not automatically multilingual master data. `LostReport` title/descriptions/location are student-authored incident text. Claim evidence and review text, private item details, custody locations/notes, and handover verification text are event/security records; preserve originals and confidentiality, not translation rows. Fixed status/event/evidence types are invariant enum values with static localized display labels. Image records and references are invariant metadata. See the task report for the field-level matrix.

No Location, Department, Faculty, Campus, Building, Room, Item Type, or database-managed Reason/Status master-data model was found inside the current LostAndFound package. These are future candidates only if a real domain requirement establishes them.
