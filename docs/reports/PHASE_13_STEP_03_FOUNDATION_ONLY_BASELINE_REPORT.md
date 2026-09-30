# Phase 13 Step 03 — Foundation-only Baseline Certification

Date: 2026-09-29  
Mode: Foundation-only default, Optional feature freeze, baseline certification, no undo.

## 1. Outcome

PASS. The repository-default development composition is now Foundation-only. Core, User, Admin, DataGrid, Installer, Web, and Theme remain always composed; Base remains the selected theme. Student, Event, and LostAndFound remain physically available and data-preserving, but are disabled by default and feature-development frozen during Foundation hardening.

Explicit all-enabled composition continues to pass the complete application suite.

## 2. Baseline and Local Environment

Before Step 03 changes, the persisted Step 02B implementation reproduced its all-enabled baseline: 554 tests / 3,525 assertions, 120 routes, zero scheduled tasks, valid Composer metadata, and a clean `git diff --check`.

The developer's local `.env` had no `CAMPUSHUB_OPTIONAL_PACKAGES` entry. No secret values were printed and `.env` was not modified. Config and route caches were initially absent.

## 3. Default Composition Change

`config/campushub.php` remains the sole authority. Its missing-environment fallback changed from `student,event,lost_and_found` to the empty string. `.env.example` now agrees:

```dotenv
CAMPUSHUB_OPTIONAL_PACKAGES=
```

The example also documents Student, Student+Event, Student+LostAndFound, and all-enabled selections. No second flag or composition mechanism was introduced.

## 4. Default Runtime Certification

The default application selected zero Optional packages and measured:

```text
69 total routes
66 application routes
3 framework routes
0 Optional routes
0 Optional Concord modules
0 Optional migration paths
0 scheduled tasks
```

The three framework routes were broadcasting authentication, Sanctum's CSRF cookie, and `/up`. `/` remained the unique `web.home` route owned by Web.

## 5. Foundation Package and Theme Classification

The authoritative classification is documented in `docs/architecture/FOUNDATION_ARCHITECTURE.md`:

| Component | Classification | Default |
| --- | --- | --- |
| Core | Foundation | always enabled |
| User | Foundation | always enabled |
| Admin | Foundation | always enabled |
| DataGrid | Foundation | always enabled |
| Installer | Foundation | always enabled |
| Web | Foundation | always enabled |
| Theme | Foundation | always enabled |
| Base | theme implementation | selected |
| Student | Optional feature | disabled |
| Event | Optional feature | disabled |
| LostAndFound | Optional feature | disabled |

Localization is explicitly recorded as responsibilities distributed across Core, Admin, Web, and Installer rather than a separate package.

## 6. Proven Foundation Dependency Graph

The seven current Composer manifests prove:

```text
Core
User      -> Core
DataGrid  -> Core
Installer -> Core, User
Admin     -> Core, DataGrid, User

Theme
Web       -> Core, Theme
```

Core and Theme have no internal dependency. No Foundation manifest requires an Optional package.

## 7. Default Core, User, Admin, and DataGrid

The expanded certification exercised all Foundation providers rather than only asserting class availability. Core's authentication redirect resolver and content locale service resolved; Foundation Concord bindings remained operational. The `user` guard/provider authenticated the seeded administrator. DataGrid's provider and SavedFilter Concord binding remained active.

Admin login and authenticated dashboard returned successfully. ACL/menu aggregation and the generic MegaSearch/Quick Create hosts rendered without Optional entries or view lookups.

## 8. Default Web, Theme/Base, and Localization

The Web root rendered through Base in English/LTR and Arabic/RTL. WebContext, navigation, sections, SEO, and a real Web component were exercised with empty Optional contributions. Theme discovery, Base resolution, and the Base inheritance chain remained operational.

Admin and Web translation keys resolved without Optional namespaces. The focused suite also retained existing Web component, navigation, SEO, theme security/inheritance, Admin locale, and Core content-locale coverage.

## 9. Default Installer Audit

Installer provider, routes, view namespace, translations, and main database seeder booted in Foundation-only mode. The active `Installer\Database\Seeders\DatabaseSeeder` calls only Core and User seeders. Installer production source contains zero Student/Event/LostAndFound namespaces, routes, translations, providers, or models.

Recorded Step 04 backlog, not changed here:

- Krayin/CRM naming and translation residue;
- unused Workflow and EmailTemplate seeders targeting non-Foundation tables;
- hard-coded example administrator credentials and commented `api_token` code;
- repeated translation registration and broader installer lifecycle/documentation review.

These are hardening items, not Foundation-only boot blockers.

## 10. Disabled Optional Contributions

Under the repository default:

- Student, Event, and LostAndFound main providers are absent;
- Student guard/provider is absent while User/Admin auth remains functional;
- Event and LostAndFound Concord modules/bindings are absent;
- Optional route actions, ACL/menu entries, view namespaces, Event navigation, and migration paths are absent;
- `lost_found_private` is absent from runtime filesystem config;
- no Foundation layout resolves an Optional view.

All three package directories and `storage/app/lost-found-private` remain present. No Optional production source changed in Step 03.

## 11. Optional Feature Freeze

Rule 08 and the architecture document now define Student, Event, and LostAndFound as available, disabled by default, and feature-development frozen during Foundation hardening. They are not deprecated or uninstalled. Changes are permitted only to correct a Foundation regression or prove compatibility.

A developer enables only the target package and its explicit dependencies. Event uses `student,event`; LostAndFound uses `student,lost_and_found`. Dependencies remain explicit and are never auto-enabled.

## 12. Foundation Source Isolation

Exact namespace/business scans over Admin, Core, DataGrid, Installer, User, Web, Theme, and Base returned:

```text
Foundation -> Student:      0
Foundation -> Event:        0
Foundation -> LostAndFound: 0
```

Generic lifecycle metadata and root PSR-4 availability remain separately classified composition infrastructure.

## 13. Test Modes

### Foundation-only focused suite

149 tests / 1,317 assertions passed. The explicit selection was:

```text
tests/Composition/FoundationOnlyApplicationTest.php
tests/Feature/Admin
tests/Feature/AuthenticationTest.php
tests/Feature/Core
tests/Feature/DataGridExportRegressionTest.php
tests/Feature/InstallerSafetyTest.php
tests/Feature/PdfStackRemovalRegressionTest.php
tests/Feature/Theme
tests/Feature/Web
```

The dedicated application certification expanded from 3 tests / 42 assertions to 10 tests / 90 assertions and now covers the repository default, boot, every Foundation provider, Core services, User auth, DataGrid, Admin, Installer, Web, Theme/Base, localization, route ownership, and all relevant Optional-absence dimensions.

The Student-specific authentication assertion formerly located in `WebRootHomepageTest` moved to Student's isolation suite, so Foundation Web coverage no longer assumes Student.

### Composition suite

25 tests / 81 assertions passed. All five valid compositions and four invalid/unknown process cases remain covered, together with metadata, reverse dependencies, duplicate IDs, missing classes, and cycles. A new regression assertion protects the empty repository fallback and `.env.example` default.

### Explicit all-enabled suite

With `CAMPUSHUB_OPTIONAL_PACKAGES=student,event,lost_and_found`, 555 tests / 3,527 assertions passed in 23.74 seconds. The increase reflects the new permanent default regression test and the correctly relocated Student/Web boundary test. All Optional functionality remains compatible.

## 14. Cache Matrix and Final State

Configuration and route caches passed for both modes:

| Cached composition | Enabled Optional IDs | Routes |
| --- | --- | ---: |
| repository-default Foundation only | none | 69 |
| explicit all-enabled | student, event, lost_and_found | 120 |

After all-enabled verification, caches were cleared and rebuilt from the repository default. Final cached state is Foundation-only with an empty enabled list and 69 routes.

## 15. Data and Dependency Safety

No migration was created or run against the runtime database. No production schema, Optional table, row, source directory, stored file, or migration history was deleted or changed. The Installer safety suite used disposable databases. No Composer or NPM dependency was added.

## 16. Foundation Hardening Backlog

The audit records these bounded next-step areas without expanding implementation scope:

- **Core:** Krayin command naming, the CRM/ecommerce-derived `Menu::CUSTOMER`, stale publishable Concord snapshot, ItemField example residue, and public extension-contract documentation.
- **User:** `api_token` policy, CRM/product wording, `view_permission` classification, and historical user-migration ownership.
- **Admin:** remaining CRM/ecommerce translations/configuration and historical user migration; maturity/documentation of dashboard, MegaSearch, quick-create, menu, ACL, and config contribution contracts.
- **DataGrid:** public query/column hook documentation and package certification.
- **Installer:** the residue listed in section 9, credential policy, and clean Foundation installation certification.
- **Theme/Web:** stable extension API documentation, theme development/selection guide, and continued boundary enforcement.
- **Base:** presentation contract, asset, accessibility, responsive, and bidirectional certification.
- **Localization:** replace remaining hard-coded direction assumptions where applicable and complete end-to-end authoring/fallback documentation after lower layers stabilize.

None blocks the certified default boot; they define Phase 13 Step 04 and later hardening work.

## 17. Recommended Hardening Order

Derived from the manifest graph and presentation layering:

1. Core
2. User and DataGrid
3. Admin
4. Theme
5. Web
6. Installer
7. Base and localization certification

Core is first because every Foundation package except Theme consumes it directly or transitively, and its generic contracts/extension boundaries must stabilize before higher-layer documentation. Theme precedes Web because Web depends on Theme. Installer follows Core/User stabilization. Base and cross-surface localization certification come after their engines and hosts.

## 18. Files Created

- `docs/architecture/FOUNDATION_ARCHITECTURE.md` — authoritative current Foundation classification, graph, composition, default workflow, freeze, and certification contract.
- `docs/reports/PHASE_13_STEP_03_FOUNDATION_ONLY_BASELINE_REPORT.md` — this report.

## 19. Files Modified

- `config/campushub.php` — empty Optional selection is now the missing-environment default.
- `.env.example` — Foundation-only default and explicit composition examples.
- `docs/rules/08_SEED_FOUNDATION_AND_PACKAGE_ISOLATION_RULES.md` — canonical Foundation-only mode, freeze, preservation, and dual-test-mode law.
- `docs/rules/README.md` — links the architecture document.
- `tests/Composition/FoundationOnlyApplicationTest.php` — removes the special override and expands real default certification.
- `tests/Feature/Foundation/OptionalPackageCompositionTest.php` — protects the repository default.
- `tests/Feature/Web/WebRootHomepageTest.php` — removes an Optional authentication assumption from Foundation Web coverage.
- `tests/Feature/Student/StudentPackageIsolationTest.php` — retains that Student-owned boundary verification in the correct suite.

No Optional production file was modified or deleted. Prior Step 02A/02B implementation files remain present. `.phpunit.cache/test-results` remains a generated modified file.

## 20. Persistence

Pre-report `git status --short`, `git diff --stat`, and `git diff --check` passed with the Step 01–03 implementation physically present. Post-report checks, critical-file reinspection, and final Foundation-only cache verification are recorded in the task handoff. No prohibited Git or undo command was used.

PHASE_13_STEP_03_STATUS:
PASS

MODE:
FOUNDATION_ONLY_DEFAULT_AND_BASELINE_CERTIFICATION_NO_UNDO

AUTHORITATIVE_COMPOSITION_SOURCE:
config/campushub.php

DEFAULT_OPTIONAL_PACKAGE_SELECTION:
empty string via env('CAMPUSHUB_OPTIONAL_PACKAGES', '')

DEFAULT_ENABLED_OPTIONAL_PACKAGES:
NONE

STUDENT_DEFAULT_STATE:
DISABLED

EVENT_DEFAULT_STATE:
DISABLED

LOST_FOUND_DEFAULT_STATE:
DISABLED

OPTIONAL_FEATURE_DEVELOPMENT_FROZEN:
YES

FOUNDATION_PACKAGES:
Core, User, Admin, DataGrid, Installer, Web, Theme

DEFAULT_THEME:
base

FOUNDATION_TO_STUDENT_BUSINESS_REFERENCES:
0

FOUNDATION_TO_EVENT_BUSINESS_REFERENCES:
0

FOUNDATION_TO_LOST_FOUND_BUSINESS_REFERENCES:
0

DEFAULT_FOUNDATION_ONLY_BOOT:
PASS

DEFAULT_FOUNDATION_ONLY_ROOT:
PASS

DEFAULT_FOUNDATION_ONLY_ADMIN:
PASS

DEFAULT_FOUNDATION_ONLY_WEB:
PASS

DEFAULT_FOUNDATION_ONLY_THEME_BASE:
PASS

DEFAULT_FOUNDATION_ONLY_LOCALIZATION:
PASS

DEFAULT_FOUNDATION_ONLY_INSTALLER:
PASS

DEFAULT_OPTIONAL_ROUTES_PRESENT:
0

DEFAULT_OPTIONAL_CONCORD_MODULES_PRESENT:
0

DEFAULT_OPTIONAL_MIGRATIONS_DISCOVERED:
0

DEFAULT_FOUNDATION_ONLY_ROUTE_COUNT:
69

DEFAULT_FOUNDATION_APPLICATION_ROUTE_COUNT:
66

FOUNDATION_ONLY_FOCUSED_TEST_SUITE:
149 passed (1317 assertions)

PACKAGE_COMPOSITION_TEST_SUITE:
25 passed (81 assertions)

ALL_ENABLED_COMPATIBILITY:
PASS

ALL_ENABLED_FULL_TEST_SUITE:
555 passed (3527 assertions)

ALL_ENABLED_ROUTE_COUNT:
120

CONFIG_CACHE_FOUNDATION_ONLY:
PASS

ROUTE_CACHE_FOUNDATION_ONLY:
PASS

CONFIG_CACHE_ALL_ENABLED:
PASS

ROUTE_CACHE_ALL_ENABLED:
PASS

FINAL_CACHE_STATE:
FOUNDATION_ONLY

OPTIONAL_SOURCE_DELETED:
NO

OPTIONAL_DATA_DROPPED:
NO

OPTIONAL_FILES_DELETED:
NO

NEW_MIGRATIONS:
0

PRODUCTION_SCHEMA_MODIFIED:
NO

RUNTIME_DATABASE_MODIFIED:
NO

NEW_EXTERNAL_COMPOSER_DEPENDENCIES:
0

NEW_EXTERNAL_NPM_DEPENDENCIES:
0

FOUNDATION_ARCHITECTURE_DOCUMENT:
docs/architecture/FOUNDATION_ARCHITECTURE.md

RULE_UPDATED:
docs/rules/08_SEED_FOUNDATION_AND_PACKAGE_ISOLATION_RULES.md

COMPOSER_VALIDATE:
PASS

DEFAULT_ROUTE_COUNT:
69

SCHEDULED_TASK_COUNT:
0

FINAL_GIT_DIFF_CHECK:
PASS

DESTRUCTIVE_GIT_COMMANDS_USED:
NO

UNDO_OR_REVERT_USED:
NO

TESTED_IMPLEMENTATION_STILL_PRESENT_AFTER_REPORT:
YES

FOUNDATION_HARDENING_BLOCKERS:
NONE

RECOMMENDED_FOUNDATION_HARDENING_ORDER:
Core -> User and DataGrid -> Admin -> Theme -> Web -> Installer -> Base and Localization certification

READY_FOR_PHASE_13_STEP_04:
YES

PHASE_13_STEP_04_RECOMMENDED_SCOPE:
Perform a deep forensic audit and hardening of Webkul\Core as the lowest shared Foundation layer, remove remaining legacy/business residue, stabilize its public contracts and extension boundaries, and document how higher Foundation and Optional packages may consume Core.
