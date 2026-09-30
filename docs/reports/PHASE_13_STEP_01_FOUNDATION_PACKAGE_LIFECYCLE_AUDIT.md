# Phase 13 Step 01 — Foundation & Package Lifecycle Forensic Audit

Audit date: 2026-09-29  
Mode: forensic audit only; no package was disabled and no production source, configuration, database, or test was modified.

## 1. Executive Summary

CampusHub physically contains ten Webkul packages: `Admin`, `Core`, `DataGrid`, `Event`, `Installer`, `LostAndFound`, `Student`, `Theme`, `User`, and `Web`. Seven are generic Foundation packages (`Core`, `User`, `Admin`, `DataGrid`, `Installer`, `Web`, `Theme`); three are optional business capabilities (`Student`, `Event`, `LostAndFound`). The Base theme is a selectable theme implementation under `themes/base`, not a Webkul package. Generic persistent localization belongs to Core, its management surface to Admin, public request resolution to Web, and installer locale handling to Installer; there is no separate Localization package.

The all-packages application is healthy: 524 tests / 3,404 assertions pass, 120 routes load, no scheduled tasks exist, and Composer validates. The generic `/` route is correctly owned by Web and renders through Theme/Base without optional-package knowledge.

Foundation-only operation is **not proven and would not be safe by provider removal alone**. Student is hard-coded in root authentication and guest redirection and in Admin/Web presentation resources. LostAndFound also owns a root filesystem disk entry. Event is cleanly absent from Foundation business source, but both Event and LostAndFound require Student. Package availability and enablement are currently conflated across root PSR-4, explicit providers, Concord modules, and cached manifests; there is no enablement flag or dependency validator.

The safe dependency order is Event, LostAndFound, then Student, but Student must not be disabled until the root/Admin/Web leaks are corrected. Existing tables should remain untouched when disabling.

## 2. Rules Read

The following active documents were read in full and applied:

- `docs/rules/README.md`
- `06_PACKAGE_AND_LOCALIZATION_RULES.md`
- `07_ADMIN_UI_PAGE_RULES.md`
- `08_SEED_FOUNDATION_AND_PACKAGE_ISOLATION_RULES.md`
- `09_PACKAGE_INTERNAL_ARCHITECTURE_AND_EXTENSION_RULES.md`
- `10_ADMIN_WEB_PRESENTATION_BOUNDARY_RULES.md`
- `11_PERSISTENCE_AND_NO_UNDO_RULES.md`
- `12_WEB_COMPONENT_KERNEL_AND_INTERACTION_RULES.md`
- `13_BASE_THEME_PRESENTATION_RULES.md`
- `LOST_AND_FOUND_PACKAGE_RULES.md`

No `AGENTS.md` exists in the inspected repository ancestry.

## 3. Current Baseline

| Check | Physical result |
| --- | --- |
| Initial worktree | Pre-existing modification: `.phpunit.cache/test-results` |
| Framework | Laravel Framework 12.61.1 |
| PHP | 8.4.24 CLI |
| Composer | `composer.json is valid` |
| Routes | 120 |
| Scheduled tasks | 0 |
| Tests | 524 passed, 3,404 assertions, 18.05s |
| Diff whitespace | `git diff --check` passed |

The reported Step 12F baseline is reproduced exactly. This is an all-packages baseline only.

## 4. Package Inventory

| Package | Namespace / Composer name | Purpose and providers | Owned surfaces and data |
| --- | --- | --- | --- |
| Admin | `Webkul\Admin`; `krayin/laravel-admin` | Admin platform shell, auth UI, ACL enforcement, menu/config aggregation, dashboard and generic registries. `AdminServiceProvider`, empty `EventServiceProvider`, empty Concord module | 49 routes; 143 views; 7 locales (two language files each); Admin assets; one migration altering `users`; no domain models; Admin-only presentation |
| Core | `Webkul\Core`; `krayin/laravel-core` | Generic configuration, ACL/menu helpers, repositories, view hooks, locale/content-locale foundation. `CoreServiceProvider`, Concord module/base | 7 migrations; `CoreConfig`, `Country`, `CountryState`, `Locale`; repositories and `ContentLocaleService`; 7 locale files; no routes/views/assets |
| DataGrid | `Webkul\DataGrid`; no package manifest | Generic grid/filter/export infrastructure. `DataGridServiceProvider`, Concord module | `SavedFilter` contract/model/repository; `datagrid_saved_filters`; no routes/views/lang/assets |
| Installer | `Webkul\Installer`; `krayin/laravel-installer` | Web/CLI installation, environment/database setup, Foundation seed data. `InstallerServiceProvider` | 6 routes, 7 views, 7 locales, assets, Core/User seeders; no owned migration/model |
| User | `Webkul\User`; `krayin/laravel-user` | Generic employee/admin identity, roles, groups, permissions. `UserServiceProvider`, Concord module | `User`, `Role`, `Group` contracts/models/repositories; 7 migration files plus `.gitkeep`; no routes/views/lang/assets |
| Web | `Webkul\Web`; `webkul/web` | Generic public shell, root, locale context, SEO, navigation/section registries and components. `WebServiceProvider` | 2 routes, 13 views, English/Arabic translations, JS component runtime; no models/migrations |
| Theme | `Webkul\Theme`; `webkul/theme` | Theme discovery, registry, resolver, inheritance and view finder. `ThemeServiceProvider` | No routes/models/migrations/views/lang/assets in package; discovers implementations under `themes/` |
| Student | `Webkul\Student`; `webkul/student` | University-backed student identity/authentication and student Admin management. `StudentServiceProvider` | 12 routes (9 Admin, 3 student auth), `Student`, repository/services/DataGrid, 2 migrations, 8 views, 7 locales, ACL/menu/config, Admin contribution hooks |
| Event | `Webkul\Event`; `webkul/event` | Event/category management, subscriptions, public event discovery. `EventServiceProvider`, Concord module | 18 routes (16 Admin, 2 Web), 7 models/3 contracts/2 repositories/3 main services, 12 migrations, 20 views, 7 locales with Admin/Web files, ACL/menu/config, Admin and Web registry/listener contributions |
| LostAndFound | `Webkul\LostAndFound`; `webkul/lost-and-found` | Found items, reports, claims, custody, evidence and handover. `LostAndFoundServiceProvider`, Concord module | 21 routes (14 employee, 7 student), 15 models/5 contracts/4 repositories/20 services, 14 migrations, 3 views, 7 locales, ACL/config; no menu or public Web contribution |

No additional business package is physically present. `themes/base` contains `theme.json`, CSS, Vite and package metadata and has zero Student/Event/LostAndFound references.

## 5. Foundation Packages

- **Core** is Foundation because configuration, persistence helpers, extension hooks, countries, and global content-locale identity are generic. Its CRM-branded version command and a customer menu constant are cleanup items, not business capability.
- **User** is Foundation because the `user` guard, employee identities, roles, groups and permissions support any Admin application. See section 23 for residue.
- **Admin** is Foundation as the authenticated employee platform and host for generic ACL/menu/configuration/registry rendering. Student-specific rendering currently violates this classification but does not change the intended ownership.
- **DataGrid** is Foundation because saved filters, querying, columns and exports are generic infrastructure used across Admin features.
- **Installer** is Foundation for a reusable seed's creation/bootstrap path, although it need not remain exposed after installation. CRM residue requires hardening.
- **Web** is Foundation because `/`, public request locale/context, SEO, generic components and contribution registries operate with zero optional package imports.
- **Theme** is Foundation because it only discovers/resolves themes and overrides views. Base is the default selectable implementation, not an always-on business package.

## 6. Optional Packages

`Student`, `Event`, and `LostAndFound` all model specific campus business capabilities and are optional. Student's identity/authentication role does not make it generic employee identity; that role belongs to User. Event and LostAndFound can conceptually be omitted. No other optional business package remains.

## 7. Package Dependency Graph

Dependency labels below reflect actual source, configuration and schema rather than Composer alone.

```text
Theme                         (Foundation; no Web dependency)
  ↑
Web ── runtime/presentation ──┘
Web ── runtime ───────────────> Core locale services

Admin ── runtime ─────────────> Core, User, DataGrid
DataGrid ── declared/runtime ─> Core
User ── declared/runtime ─────> Core
Installer ── runtime ─────────> Core, User

Student ── runtime/presentation -> Admin, Core, DataGrid
Event ── runtime/presentation --> Admin, Core, DataGrid, Web
Event ── declared/runtime/database/formal composition --> Student
LostAndFound ── runtime/presentation --> Core, DataGrid, User, Admin middleware
LostAndFound ── runtime/database --> Student

Student -X-> Event, LostAndFound
Event -X-> LostAndFound
LostAndFound -X-> Event
```

Classification examples:

| Type | Evidence |
| --- | --- |
| `DECLARED_DEPENDENCY` | Event manifest requires `webkul/student` and `webkul/web`; Web requires Theme; User requires Core |
| `RUNTIME_DEPENDENCY` | Optional providers resolve Admin registries/middleware, Core hooks, repositories and services |
| `PRESENTATION_DEPENDENCY` | Optional Admin views consume `x-admin`; Event Web views consume Web shell/components |
| `DATABASE_DEPENDENCY` | `event_student` and multiple LostAndFound tables reference `students`; LostAndFound also references `users` |
| `FORMAL_COMPOSITION` | Root PSR-4 + `bootstrap/providers.php` + `config/concord.php` |
| `TEST_ONLY_DEPENDENCY` | Optional HTTP tests create User/Role actors and the all-packages test bootstrap enables everything |
| `ACCIDENTAL_COUPLING` | root Student auth/redirects, Admin Student UI, Web `student_portal` labels, root LostAndFound disk, incomplete manifests |

Dependents: Student is required by Event and LostAndFound; Web is required by Event; Admin/Core/DataGrid/User are consumed by business packages. Student has no optional dependent in its own source direction.

## 8. Foundation → Optional Violations

Direct namespaces alone undercount the problem. Excluding explicit root composition records, eleven executable/resource files contain Student business knowledge: `bootstrap/app.php`, `config/auth.php`, Admin's exception handler/provider, two Admin locale files, three Admin header/quick-create views, and two Web locale files. Admin's empty Front route also has a stale Student-root comment. These references create route, guard, model, translation and presentation coupling.

Event business coupling in Foundation production behavior is zero. Old Admin locale text uses generic/CRM “event” wording, but does not reference Event routes, namespace, tables, config, views or ACL and is classified as CRM translation residue rather than a dependency.

LostAndFound has one semantic root reference: `config/filesystems.php` defines `lost_found_private`. It does not prevent boot when the package provider is absent, but it is package configuration outside the package.

Explicit provider, Concord and PSR-4 entries are composition metadata and are counted separately, not as Foundation business references.

## 9. Optional → Foundation Dependencies

- Student uses Admin `MegaSearch`, Admin middleware/components, Core view-render hooks/helpers, DataGrid, Laravel/Guzzle, and the root `student` guard.
- Event uses Admin dashboard/search registries and presentation, Core repository/view hooks, DataGrid, Web navigation/context/components, and User-backed Admin authentication/ACL.
- LostAndFound uses Core repositories, DataGrid, User models for staff/custody/history, Admin middleware/components, Laravel storage, and the root private disk.
- All are legitimate in direction, but most are missing from package Composer metadata.

## 10. Optional → Optional Dependencies

Production direct namespace occurrence metric (`src`, exact `Webkul\<Package>` token):

| Direction | Count | Meaning |
| --- | ---: | --- |
| Student → Event | 0 | Student runs without Event |
| Student → LostAndFound | 0 | Student runs without LostAndFound |
| Event → Student | 5 | Models, listener, dashboard service, subscription controller, provider; plus routes/config/schema semantics |
| Event → LostAndFound | 0 | Event runs without LostAndFound |
| LostAndFound → Student | 8 | Models and application/domain services; plus auth routes and schema FKs |
| LostAndFound → Event | 0 | LostAndFound runs without Event |

Therefore Event cannot run without Student. LostAndFound cannot run without Student. Student can run without either. Event and LostAndFound are mutually independent.

## 11. Package Registration Architecture

The exact activation path is:

1. Root `composer.json` directly maps every Webkul namespace to source. Root does **not** require the local package Composer packages.
2. `bootstrap/providers.php` explicitly registers every main provider.
3. Main providers load routes, views, translations, migrations, config, bindings, listeners and registries.
4. `config/concord.php` explicitly registers model modules for Admin, Core, DataGrid, User, Event and LostAndFound. Student uses its concrete model directly and has no Concord module.
5. Laravel/Composer cache generated files such as `bootstrap/cache/services.php`; cache refresh is operationally required after composition changes.
6. Theme separately scans configured directories for `theme.json`; this is theme discovery, not business-package discovery.

Package `extra.laravel.providers` metadata exists, but because local package manifests are not root requirements, it is not what activates these packages in this application.

## 12. Current Activation Model

“Enabled” currently means the namespace is root-autoloadable, the main provider is explicitly registered, any Concord module is registered, and generated caches agree. There is no activation flag, package registry, environment switch or database state. Composer package metadata does not control current activation.

## 13. Current Deactivation Model

Today deactivation is a manual deployment edit: remove the main provider, remove its Concord module if present, refresh Composer/Laravel caches, and correct every host-level runtime reference. Keeping PSR-4 merely leaves code available and is not activation. Database changes are not required.

- Student: provider removal plus root auth/redirect and Admin/Web cleanup; no Concord removal.
- Event: provider and Concord module removal; Student remains valid.
- LostAndFound: provider and Concord module removal; its root disk may harmlessly remain but should move to package-owned configuration.

## 14. Composer Analysis

Root PSR-4 hardwires all ten namespaces. This is acceptable availability for a monorepo but is not enablement and should not be mistaken for a package dependency graph.

Manifest defects are material: DataGrid has no `composer.json`; LostAndFound declares no dependencies; Student declares framework/Guzzle but omits Admin/Core/DataGrid; Event declares Student/Web but omits Admin/Core/DataGrid/User; Web omits its runtime Core dependency; Admin retains obsolete Krayin Attribute/Contact/Email/Lead/Product/Tag/UI requirements not present in root composition. Root name/description remain `krayin/laravel-crm` / `Krayin CRM`. These are architecture leakage or stale metadata, not a trustworthy install graph.

## 15. Provider Analysis

Providers are package composition roots and generally self-load owned resources. Event and Student correctly contribute via generic registries/hooks. LostAndFound self-loads routes/resources but has no menu contribution. Admin incorrectly seeds a `students` MegaSearch tab and its core layouts render Student-specific search/quick-create UI. Provider order currently matters: Admin/Core/DataGrid/Installer/User/Event/Student/LostAndFound/Theme/Web. Event resolves Web and Student services during boot even though those providers appear later; Laravel registration completes before boot, so it works but is fragile to alternate composition.

## 16. Concord Analysis

Core registers CoreConfig/Country/CountryState/Locale; DataGrid registers SavedFilter; User registers Group/Role/User; Event registers EventCategory/Event/EventField; LostAndFound registers five principal models. Admin's module is empty. Event and LostAndFound modules must be removed whenever their main providers are disabled, or their contracts/proxies remain registered. Student has no Concord contract/proxy, so its consumers directly bind to the concrete class. Foundation contains no Event/LostAndFound Concord binding, but root `config/concord.php` does.

## 17. Route Ownership

Current route ownership is exact:

| Owner | Count | Categories |
| --- | ---: | --- |
| Admin | 49 | auth, account, dashboard/stats, configuration, settings/users/roles/groups/languages, DataGrid, TinyMCE |
| Student | 12 | 9 Admin student CRUD/search routes; 3 student session routes |
| Event | 18 | 14 event/category Admin routes, 2 Student-subscription Admin routes, 2 public Web routes |
| LostAndFound | 21 | 14 employee/Admin routes, 7 student routes |
| Installer | 6 | installer UI/API and image cache |
| Web | 2 | `/` and locale switch |
| Framework/vendor | 12 | health, broadcasting, Sanctum, Debugbar and Ignition |

Core, User, DataGrid and Theme own zero routes. `/` is uniquely `web.home`, owned by Web.

## 18. Admin Ownership

Package-owned ACL/menu/config merge naturally disappears with a provider. Event dashboard cards, Student details, search and quick actions mostly use generic hooks. However Admin itself registers and renders Student search/quick creation and redirects `student` guard failures. With all optional providers absent, route generation for `admin.students.*` can break Admin layouts, especially an all-permission administrator. Admin translations also retain Student portal/Student management and broad CRM/ecommerce residue. LostAndFound ACL contains several route names that do not physically exist, and it has no menu entry; this is package-internal incompleteness rather than Foundation ownership.

## 19. Web Ownership

Web owns `/`, locale switch, context, generic layout/components, navigation and sections, and SEO. Its PHP, routes and views have zero optional namespaces/routes/views/tables. Two Web locale files include a `student_portal` label; this is semantic Student residue and must be generalized or moved. Event correctly contributes its own navigation and pages through Web contracts. With that label corrected, Web can run empty of optional navigation.

## 20. Theme Ownership

Theme contains registry, resolver, inheritance, metadata parsing and the view finder only. It has no routes or optional knowledge. `themes/base` contains presentation assets only and has zero optional references. Theme engine enablement is Foundation composition; selecting `APP_THEME=base` is a separate presentation choice.

## 21. Localization Ownership

Generic database locale identity and `ContentLocaleService` belong to Core. Admin owns website-language management and its independent `admin_locale`; Web owns public locale selection/context; Installer owns only its install-session locale. Static strings remain package-owned (`student::`, `event::`, `lost_found::`, etc.). Admin UI locale and Web/content locale are physically distinct. Public dynamic locale remains functional without optional packages. There is no separate Localization package.

## 22. Core Audit

Core has no Student/Event/LostAndFound table, route, view or service dependency. Generic locale ownership is appropriate. Cleanup: the command signature/description says Krayin CRM; `Menu::CUSTOMER` is ecommerce/CRM-derived terminology; `src/Config/concord.php` is a stale publishable composition snapshot that imports Admin/DataGrid/User from Core. The Event-specific example in an ItemField comment explains dotted generic field behavior and is low-impact semantic residue.

## 23. User Audit

User is generic employee/admin identity: the `user` guard, roles, groups and permission arrays are reusable. It contains no optional imports. Cleanup before permanent certification: rename product-image docblocks; decide whether `api_token` (hidden/fillable but not created by the shown base migration) is supported; reclassify/remove CRM-origin `view_permission`; and move Admin's migration that alters `users` to the owning User package through a forward migration policy. These do not make Student foundational.

## 24. Admin Audit

Admin is a platform host but violates that role through Student-specific provider registration, search templates, quick creation, exception redirection and translations. It also retains CRM/ecommerce translation/config residue and a migration named for lead view permission. Dashboard content itself is generic and optional metrics are registry contributions. ACL/menu aggregation is generic; optional definitions are package-owned.

## 25. DataGrid Audit

DataGrid has no business-package imports or assumptions. Saved filters and export/query infrastructure are generic. Its missing package manifest is the primary lifecycle defect. It correctly depends on Core's repository/base module conventions and is Foundation.

## 26. Installer Audit

The active Installer database seeder calls only Core and User seeders, so a normal current install does not seed Student/Event/LostAndFound. It still contains unused EmailTemplate and Workflow seeders targeting absent tables, extensive CRM/activity translations, Krayin branding, an “Admin & Shop” comment, and a default `admin@example.com` / `admin123` credential path. Installer migrations are empty; application migrations are discovered from enabled providers. A Foundation-only install is therefore plausible but not certified until optional providers can actually be excluded and obsolete seeders/branding/default-credential policy are hardened.

## 27. Web Audit

Web's dependency direction is correct: Core locale plus Theme resolution, never optional packages. Root, middleware, navigation label resolution, sections, SEO and components are tested. The only business-semantic leak found is `student_portal` in English/Arabic translations. Web's manifest must declare Core.

## 28. Theme Audit

Theme and Base comply with isolation: no Admin or optional dependency, no business routes/queries, isolated assets, protected namespaces and generic override resolution. Theme selection must remain separate from capability activation. A theme-development guide and packaging decision are missing.

## 29. Migration Ownership

Foundation tables: Core owns `core_config`, `countries`, `country_states`, `locales`, `content_locale_settings`; User owns `groups`, `roles`, `users`, `user_groups`, `user_password_resets`; DataGrid owns `datagrid_saved_filters`. Admin improperly owns a historical alteration of `users`.

Optional tables: Student owns `students`; Event owns `event_categories`, `events`, `event_fields`, historical `event_related`, `event_event_category`, `event_student`, `event_images`; LostAndFound owns all 14 `lost_found_*` migrations/tables. Foundation creates no optional table. Event's `event_student` and LostAndFound reports/claims/handovers reference `students`; LostAndFound also references `users`.

## 30. Database Disable Policy

Disable must not run down migrations or drop tables. Existing optional data and foreign keys remain preserved and dormant. Current architecture supports that policy because route/migration loading stops with providers while existing schema remains usable by later re-enable. Uninstall needs a separately designed, explicit, dependency-aware and destructive process. Database backup, retention and FK order are mandatory for that future process.

## 31. Disable vs Uninstall

**Disable:** stop provider/module/resource loading; preserve source, autoload availability, migrations, schema and data; refresh caches; allow re-enable.  
**Uninstall:** first disable and validate reverse dependencies, then deliberately remove composition/code and optionally data through an explicit retention/destruction plan. Uninstall is never an alias for provider removal or migration rollback.

## 32. Proposed Package Lifecycle

Use only `AVAILABLE`, `ENABLED`, `DISABLED` now. Availability means trusted code/autoload metadata exists. Enabled means deployment composition includes its provider/module after dependency validation. Disabled means available but not composed; code and data remain. Reserve `NOT_INSTALLED` until real code installation is required. Enabling Event must reject a disabled Student; disabling Student must reject while Event or LostAndFound is enabled.

## 33. Foundation Always-On Decision

Yes. Core, User, Admin, DataGrid, Installer, Web and Theme should be fixed Foundation composition, not user-toggleable modules. Installer access is lifecycle-gated after installation, but the package remains Foundation. Only optional business packages enter the activation lifecycle.

## 34. Theme Lifecycle Boundary

Theme Engine is always-on Foundation. Base is an available/selectable presentation implementation discovered through a safe manifest. Optional packages are capabilities with providers, routes and data. `APP_THEME` selection must never enable or disable business packages.

## 35. Extension Contract Inventory

| Extension | Verdict |
| --- | --- |
| Web `NavigationRegistryContract`, request-late labels | STABLE; needs developer documentation |
| Web `SectionRegistryContract` | STABLE core API; usage needs documentation |
| Web `SeoMetadataContract` | STABLE; needs documentation |
| Web Blade component kernel | STABLE and rule-backed |
| Theme registry/resolver/inheritance/view overrides | STABLE; needs theme guide |
| Admin ACL/menu/core-config merge | STABLE mechanism; contribution contract needs documentation |
| DashboardStatsRegistry / MegaSearch / quick-create hooks | NEEDS_REDESIGN around package-neutral default state and boot-time labels, then documentation |
| DataGrid and its query/column events | STABLE infrastructure; string hook compatibility needs documentation |
| Concord contracts/proxies | STABLE external mechanism; module lifecycle/ordering needs documentation |
| Core view-render string events | INTERNAL/NEEDS_DOCUMENTATION; names and payloads are compatibility surfaces |
| Core content locale and Web request locale | STABLE foundation split; end-to-end authoring/fallback guide needed |
| Laravel events/listeners | INTERNAL unless explicitly published by an owning package |

## 36. Package Directory Standard

Use the repository's existing, need-driven structure: `Config`, `Contracts` only for genuine model/public contracts, `Models`, `Repositories`, cohesive `Services` (optionally `Services/Application`), `Http/Controllers`, `Http/Requests`, `DataGrids`, `Events`/`Listeners`, `Providers`, `Database/Migrations`, `Database/Seeders`, `Resources/views`, `Resources/lang`, `Resources/assets`, and `Routes`. Do not create empty layers. Require `composer.json` plus one architecture manifest/document for optional packages.

## 37. Presentation Standard

Package Admin presentation belongs in `Http/Controllers/Admin` or the established employee-equivalent boundary, `DataGrids/Admin`, `Resources/views/admin`, package ACL/menu/config and package translations, while consuming Admin components. Public presentation belongs in `Http/Controllers/Web`, `Resources/views/web`, package `web.php` translations and Web registries/components. Domain/application services and repositories are shared below both; Admin/Web packages never own feature behavior.

## 38. Route Standard

Admin routes are package-owned, prefixed by `config('app.admin_path')`, use `web`, `admin_locale`, `user`, and Bouncer enforcement, and use `admin.<package>.*` names. Public routes are package-owned and use `web` + `web_context`, with `<package>.web.*` names. Student-authenticated feature routes currently use `web`, `admin_locale`, `auth:student`; this legacy locale boundary should move to a future Student/public content-locale contract. Providers load route files; Foundation never requires optional route files.

## 39. Translation Standard

Each package owns static UI keys under its namespace and `Resources/lang/<locale>`. English is the reference structure; every locale shipped by that package must have key parity. Student and LostAndFound ship 7 `app.php` locales; Event ships 7 `app.php` plus 7 `web.php`; Web ships English/Arabic. Admin UI locale stays independent from Core content locales/Web selection. Content locale identity/default/direction comes from Core and user-authored content must use explicit model-owned translation storage, not static language files.

## 40. Testing Standard

Mandatory useful categories are: package architecture/ownership; unit tests for state/security services; persistence/migration/FK tests; HTTP authentication/authorization/validation tests; Admin contribution tests; Web presentation/SEO/navigation tests when applicable; localization parity and locale behavior; isolation/dependency-direction tests; disable/removability composition tests; and regression tests for previously fixed cross-cutting behavior. Not every package needs every category.

## 41. Removability Test Standard

Create a non-destructive test composition that builds provider/module lists from a controlled package config. For each optional package and the all-disabled case: boot a fresh process with it disabled; assert no Foundation production token imports it; assert its provider/module/migration path/routes/views/translations/ACL/menu/config/registry listeners are absent; assert `/`, Admin login/dashboard and locale paths work; assert retained tables are untouched. Then enable dependency sets and assert invalid enable/disable combinations fail before cache creation. Physical-source deletion tests belong only in disposable CI copies.

## 42. Foundation-Only Target

Enabled Webkul packages: Core, User, Admin, DataGrid, Installer, Web, Theme. Selected presentation: Base theme. Localization remains distributed across Core/Admin/Web/Installer. Student, Event and LostAndFound are available but disabled, with code and data preserved. This exact set is valid after the Student/root/Admin/Web corrections and lifecycle composition work; it is not proven today.

## 43. Foundation-Only Route Target

App-owned routes predicted from actual ownership: Admin 49, Installer 6 and Web 2, for 57 application routes. In the current local environment another 12 framework/dev routes produce 69 total, but Debugbar/Ignition counts are environment-dependent and must not be a certification constant. No Student, Event or LostAndFound route/navigation may remain. `/` stays `web.home`.

## 44. Foundation-Only Test Target

Run Core locale, Admin language/auth foundation, DataGrid regression, Installer safety, User/auth, Web, Theme/Base, generic runtime/security and dependency-isolation tests. Current `RuntimeSafetyTest`, `SecurityBoundaryTest` and shared bootstrap contain Student expectations and must be split so Foundation tests never require Student routes/models. Optional suites must be excluded from Foundation-only composition rather than merely skipped after boot failure.

## 45. CI Matrix Proposal

Use: Foundation only; Foundation + Student; Foundation + Student + Event; Foundation + Student + LostAndFound; and all packages. Event-only and LostAndFound-only are invalid because Student is required. Each matrix boots a fresh cached/uncached application, lists routes, runs migrations into a disposable database, and runs its applicable suites.

## 46. Development Mode Proposal

Developers should select an optional package set in deployment configuration. The composer resolves transitive requirements: selecting Event enables Student; selecting LostAndFound enables Student; selecting Student enables no other optional package. Invalid explicit disables fail with a dependency message. This supports focused work without deleting code or data.

## 47. Activation Configuration Options

Composer should express availability/install dependencies, not per-deployment enablement. Use one cacheable PHP configuration/composition source for optional enablement, optionally fed by environment before `config:cache`; do not query a database during boot. A runtime database flag is unnecessary and unsafe at this stage. Existing provider/concord lists should be generated from the validated composition rather than maintained independently.

A package metadata contract is required because Composer's Laravel provider metadata cannot express Foundation/optional type, Concord module, required optional packages or lifecycle policy. Prefer one canonical `extra.campushub` block in each Composer manifest (id, name, version, type, main provider, module provider, required packages) over a duplicate custom file, with validation at build time.

## 48. Production Risks

Risks are stale route/config caches; provider/module list divergence; dangling Concord proxies; root auth models or route generation for disabled packages; boot-time registry resolution/order; retained optional ACL/menu/search/config; migrations accidentally running for disabled code; foreign-key dependency order; hidden listeners/model callbacks; and environment/config-cache disagreement. Deployment must validate dependencies, compile one composition, clear/rebuild caches atomically, smoke-test routes/Admin/Web, and never change schema during disable.

## 49. Security Boundary

Package activation is trusted application/deployment configuration. It must not be exposed to students, ordinary employees or a public runtime UI. Only trusted code is eligible; no arbitrary dynamic PHP loading, downloader, marketplace or license system is warranted.

## 50. Technical Debt

Concrete issue count: **0 CRITICAL, 4 HIGH, 8 MEDIUM, 3 LOW**.

HIGH: (1) root auth/guest redirection hard-depends on Student; (2) Admin/Web render Student-specific links/labels outside Student; (3) no single activation/dependency validator across providers and Concord; (4) package manifests are incomplete/unreliable, including LostAndFound's empty requirements and missing DataGrid manifest.

MEDIUM: (1) LostAndFound disk in root config; (2) Installer CRM/workflow/email residue and default credential flow; (3) Core CRM naming/customer constant/stale Concord snapshot; (4) User `view_permission`/API-token/product wording residue and migration ownership; (5) Admin CRM/ecommerce translations and migration residue; (6) LostAndFound ACL references nonexistent routes and lacks coherent navigation; (7) no Foundation-only test/bootstrap matrix; (8) cached service/provider artifacts can diverge from manual composition.

LOW: (1) root/package names and descriptions retain Krayin CRM branding; (2) Installer loads translations three times; (3) duplicated `admin/datagrid/datagrid/*` route segments reduce convention clarity.

## 51. Student Disable Readiness

**NO.** Disabling only its provider removes its routes/views/migrations/contributions, but `config/auth.php` still imports its model and defines guard/provider; `bootstrap/app.php` still generates `student.login`; Admin handler/layout/provider generates Student routes and tabs; Web/Admin translations retain Student semantics. Event and LostAndFound also require it. Correct these host leaks and disable both dependents first.

## 52. Event Disable Readiness

**YES, operationally manual.** Remove Event main provider and Concord module together and rebuild caches. Its 18 routes, migrations, ACL/menu/config, dashboard/search/Student-detail listeners and Web navigation then disappear. Foundation has zero Event business dependency; Student and LostAndFound do not require Event. Preserve Event tables/data.

## 53. LostAndFound Disable Readiness

**YES, operationally manual.** Remove its main provider and Concord module together and rebuild caches. Its 21 routes, migrations, views, translations, ACL and bindings disappear. Student/Event do not require it. The root private disk remains unused and should later become package-owned; it does not prevent boot. Preserve all LostAndFound tables/data.

## 54. Required Pre-Disable Corrections

1. Make the root auth/guest redirect configuration package-neutral or composition-contributed.
2. Move/remove Admin Student MegaSearch, quick-create, route generation, exception behavior and translations; remove Web `student_portal` semantics.
3. Introduce one validated provider + Concord composition source with dependency and reverse-dependency checks.
4. Correct Composer metadata and add DataGrid metadata; include Core/Admin/User/DataGrid/Web dependencies actually used.
5. Move the LostAndFound disk definition behind package-owned merged config without changing stored data/path.
6. Add Foundation-only boot/route/Admin/Web/Theme/localization tests before disabling Student.

## 55. Safe Disable Order

`Event → LostAndFound → Student`, with Event/LostAndFound interchangeable because they do not depend on each other. Student is last because both depend on it. Stop before Student until section 54 corrections pass. For each package remove both main and Concord registration where applicable, rebuild caches, and retain schema/data.

## 56. Documentation Gaps

Seven authoritative guide-level gaps remain: Foundation architecture/package classification; package development/composition; package lifecycle/dependencies/data policy; Admin/Web presentation ownership and extension contracts; theme development/selection; localization split and content contract; and testing/CI/removability. Existing rules are strong constraints but are not a complete start-to-finish developer workflow. Student/Event manifests are uneven and LostAndFound has none.

## 57. Documentation Roadmap

Keep a small set under `docs/architecture/` or the repository's chosen permanent-docs directory: `FOUNDATION_ARCHITECTURE.md`, `PACKAGE_DEVELOPMENT_GUIDE.md`, `PACKAGE_LIFECYCLE.md`, `PRESENTATION_EXTENSION_GUIDE.md` (Admin + Web boundaries), `THEME_DEVELOPMENT_GUIDE.md`, `LOCALIZATION_GUIDE.md`, and `TESTING_AND_CI_GUIDE.md`. Rules remain normative; these guides become workflows and link to rules instead of duplicating them.

## 58. Recommended Phase 13 Roadmap

1. 13-01: this audit.
2. 13-02A: correct root/Admin/Web Student leakage and package metadata without disabling packages.
3. 13-02B: add deterministic package composition/dependency validation and cache-safe tests.
4. 13-03: disable Event, LostAndFound, then Student non-destructively; prove Foundation-only boot/routes/UI/localization.
5. 13-04: harden Core/User/Admin/DataGrid/Installer and remove CRM residue.
6. 13-05: formalize lifecycle/manifest and disable-vs-uninstall data policy.
7. 13-06: publish Foundation and extension documentation.
8. 13-07: publish package/presentation/localization development standards.
9. 13-08: run Foundation-only and dependency-set CI matrices.
10. 13-09: re-enable each valid optional composition and prove preserved data/capability.
11. 13-10: certify the reusable seed.

This sequence inserts cleanup/composition proof before the originally proposed disable step because Student cannot safely be disabled today.

## 59. Verification Results

Executed without source/database modification:

```text
git status --short
  M .phpunit.cache/test-results     (already present before audit/report)

git diff --check
PASS

php artisan --version
Laravel Framework 12.61.1

php -v
PHP 8.4.24 (cli)

composer validate --no-interaction
./composer.json is valid

php artisan route:list
120 routes

php artisan schedule:list
No scheduled tasks have been defined.

php artisan test
524 passed (3404 assertions), 18.05s
```

No Foundation-only provider mutation was performed, so Foundation-only boot remains `NOT_PROVEN`, as required by audit-only mode.

## 60. Final Verdict

The package classification and one-way dependency shape are sound in principle, and Web/Theme/Base/Event isolation is strong. CampusHub cannot yet be certified as a complete generic Foundation with all three optional packages disabled: Student still leaks into root runtime configuration and Foundation presentation, lifecycle composition is manual, and dependency manifests are not authoritative. Proceed first with the non-destructive pre-disable correction/composition slice, then disable in reverse dependency order while retaining all data.

PHASE_13_STEP_01_STATUS:
PARTIAL_PASS

MODE:
FORENSIC_AUDIT_ONLY

PRODUCTION_FILES_MODIFIED:
0

PACKAGE_INVENTORY:
Admin, Core, DataGrid, Event, Installer, LostAndFound, Student, Theme, User, Web

FOUNDATION_PACKAGES:
Core, User, Admin, DataGrid, Installer, Web, Theme

OPTIONAL_PACKAGES:
Student, Event, LostAndFound

FOUNDATION_TO_STUDENT_BUSINESS_REFERENCES:
11

FOUNDATION_TO_EVENT_BUSINESS_REFERENCES:
0

FOUNDATION_TO_LOST_FOUND_BUSINESS_REFERENCES:
1

STUDENT_TO_EVENT_REFERENCES:
0

STUDENT_TO_LOST_FOUND_REFERENCES:
0

EVENT_TO_STUDENT_REFERENCES:
5

EVENT_TO_LOST_FOUND_REFERENCES:
0

LOST_FOUND_TO_STUDENT_REFERENCES:
8

LOST_FOUND_TO_EVENT_REFERENCES:
0

CURRENT_PACKAGE_ACTIVATION_MECHANISM:
Root PSR-4 availability plus explicit bootstrap/providers.php main-provider registration plus config/concord.php module registration, with provider-owned resource loading and refreshed Laravel/Composer caches; no enablement flag or database state.

CURRENT_PACKAGE_DEACTIVATION_MECHANISM:
Manually remove the main provider and applicable Concord module, correct host-level references, then rebuild Composer/Laravel caches; preserve code, schema, and data.

FOUNDATION_CAN_BOOT_WITHOUT_OPTIONAL_PACKAGES_TODAY:
NOT_PROVEN

STUDENT_DISABLE_READINESS:
NO

EVENT_DISABLE_READINESS:
YES

LOST_FOUND_DISABLE_READINESS:
YES

SAFE_DISABLE_ORDER:
Event, LostAndFound, Student (Student only after pre-disable corrections; Event and LostAndFound may swap)

FOUNDATION_ALWAYS_ON_RECOMMENDATION:
YES

OPTIONAL_PACKAGE_LIFECYCLE_REQUIRED:
YES

PACKAGE_MANIFEST_REQUIRED:
YES

DATABASE_DRIVEN_ACTIVATION_REQUIRED:
NO

FOUNDATION_ONLY_TARGET_PACKAGES:
Core, User, Admin, DataGrid, Installer, Web, Theme; selectable Base theme; localization owned across Core/Admin/Web/Installer

FOUNDATION_DOCUMENTATION_GAPS:
7

CRITICAL_ARCHITECTURE_ISSUES:
0

HIGH_ARCHITECTURE_ISSUES:
4

MEDIUM_ARCHITECTURE_ISSUES:
8

LOW_ARCHITECTURE_ISSUES:
3

BASELINE_FULL_TEST_SUITE:
524 passed (3404 assertions), 18.05s

COMPOSER_VALIDATE:
PASS

ROUTE_COUNT:
120

SCHEDULED_TASK_COUNT:
0

RUNTIME_DATABASE_MODIFIED:
NO

DESTRUCTIVE_GIT_COMMANDS_USED:
NO

UNDO_OR_REVERT_USED:
NO

REPORT_CREATED:
YES

READY_FOR_PHASE_13_STEP_02:
NO

PHASE_13_STEP_02_RECOMMENDED_SCOPE:
Disable all verified Optional packages non-destructively while preserving their code and database data, then prove Foundation-only boot, routes, Admin, Web, Theme, localization, and tests.
