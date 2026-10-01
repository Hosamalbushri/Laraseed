# Laraseed Package Internal Architecture and Extension Rules

> **Permanent package standard.** Every new package and every package-remediation wave MUST follow this rule together with Rules 06, 07, and 08. The target is a self-owned Bagisto-style package adapted to the mechanisms actually installed in Laraseed—not ceremonial folders or blind copying.

## 1. Governing outcome

A package must look as if it was designed correctly from the beginning. It owns its domain, persistence, application behavior, routes, authorization, presentation, configuration, localization, extensions, and tests. It consumes Foundation infrastructure; Foundation does not know it exists.

After migration, exactly one authoritative implementation remains. `NO_LEGACY_RESIDUE` is a mandatory completion gate, not an optional cleanup phase.

## 2. Canonical conceptual structure

Use only the layers the package genuinely needs:

```text
Package/src/
├── Config/
├── Contracts/
├── Models/
├── Repositories/
├── Services/ or Services/Application/
├── Http/Controllers/
├── Http/Requests/
├── Http/Resources/
├── DataGrids/
├── Events/
├── Listeners/
├── Providers/
├── Database/Migrations/
├── Database/Seeders/
├── Resources/views/
├── Resources/lang/
└── Routes/
```

**Do not create empty layers.** Architecture is responsibility and ownership, not maximum directory count.

## 3. Feature self-ownership

An optional package owns all applicable feature-specific models, contracts, repositories, services, controllers, requests, resources, DataGrids, routes, views, translations, ACL, menu, configuration, migrations, seeders, events, listeners, notifications, assets, and tests.

Admin is a host surface, not a feature dumping ground. Package Admin pages belong under package-owned `Http/Controllers/Admin`, `DataGrids/Admin`, `Resources/views/admin`, routes, ACL, menu, and translations. They may consume `x-admin::*` components without copying or modifying those components to introduce feature knowledge.

## 4. ServiceProvider as composition root

The main package ServiceProvider is the package composition root. It may:

- load package routes, views, translations, and migrations;
- merge package config, ACL, and menu contributions;
- bind contracts and cohesive services;
- register event/listener mappings;
- register runtime relations, view fragments, and generic registry contributions;
- delegate large registration areas to specialized package-owned providers.

A provider composes objects and registrations. It must not execute business workflows, perform request-time database work, or become a god class. Config files describe values/contributions and must not perform database operations or workflows.

Removing or unregistering the package must naturally remove its provider-owned contributions.

## 5. Verified Laraseed composition mechanisms

Laraseed currently uses:

- Laravel ServiceProviders and container bindings;
- `loadRoutesFrom`, route groups, `loadViewsFrom`, `loadTranslationsFrom`, and `loadMigrationsFrom`;
- `mergeConfigFrom` plus the Core ACL/menu/system-config aggregators;
- Konekt Concord `ModuleServiceProvider` model registration;
- model Contracts and `ModelProxy` resolution;
- Laravel events/listeners and stable string events;
- Core `view_render_event()` with `ViewRenderEventManager`;
- Laravel Eloquent `resolveRelationUsing()` for relation-only extension when adopted;
- generic registries such as Dashboard metrics and Mega Search;
- DataGrid query/column lifecycle events;
- dependency injection into controllers and services;
- project repositories based on `Webkul\Core\Eloquent\Repository`.

These mechanisms are available tools, not requirements to use every tool in every package.

## 6. Concord contract/model/proxy rule

The established model path is:

```text
Contract -> Concord-registered implementation -> ModelProxy -> runtime model
```

A package's ModuleServiceProvider lists models. Concord derives or accepts the contract, verifies that the implementation satisfies it, records the binding, aliases it in the container, resets the proxy, and optionally registers route-model bindings. Repositories that return a Contract allow the resolved implementation to be substituted.

Use this pattern when model replacement/extension or established repository conventions justify it. Do not create Contracts and Proxies mechanically for every small class.

## 7. Model replacement

An extension package may define an extended model that intentionally extends the original implementation, preserves the original contract and required behavior, and registers the contract-to-extended-model mapping through its ModuleServiceProvider. The extension dependency and registration order must be explicit and tested.

Concord replacement is not unlimited composition. `Concord::registerModel()` stores one effective implementation for a contract; later registration can replace an earlier mapping. Two packages competing for the same contract therefore require explicit compatibility and ordering analysis. Silent monkey-patching or order-dependent replacement is forbidden.

## 8. Runtime relation extension

When a package only needs to add a relationship to a model it does not own, prefer package-owned `Model::resolveRelationUsing()` registration when it provides a stable, testable relation without model substitution.

The extension package registers the resolver and owns the related model/query. The original package must not import the extension merely to expose the relation. Relation injection does not erase a real dependency: the extending package must still declare its dependency on the original model package. Relation names and semantics are public extension API and collision-prone, so test them.

## 9. Repositories and persistence boundaries

Normal aggregate access follows the established direction where appropriate:

```text
Controller -> Application/Service -> Repository -> Contract/Proxy -> Model
```

Repositories own persistence, storage-oriented querying, aggregate retrieval, and enforcement that protects generic persistence APIs. They must not become dumps for authorization, notifications, presentation, unrelated workflows, or every business invariant. Independent transaction, workflow, security, external-integration, or lifecycle responsibility belongs in a cohesive application/domain service.

Controllers and ordinary services must not casually query models directly when a repository/application boundary is established. Limited model lookup may be justified at a deliberate boundary, but it must not bypass authorization, transaction, aggregate, or workflow invariants.

### DataGrid exception

Laraseed DataGrids may build specialized read query builders directly because the DataGrid abstraction owns filtering, sorting, joins, pagination, export, and query lifecycle events. This exception is limited to DataGrid/read-model composition. It must not spread into ordinary controllers or services.

## 10. Services and transaction boundaries

Split services by business responsibility, transaction boundary, workflow invariant, security boundary, or external integration—not by arbitrary method or line counts.

A service that mixes unrelated CRUD, approval, registration, messaging, upload, dashboard, reporting, export/import, search, and notification workflows requires decomposition. The opposite extreme—one meaningless service per method—is also forbidden. Names must describe business responsibility; avoid `Manager`, `Utils`, `CommonService`, `MainService`, or `GeneralRepository` unless that is truly the domain concept.

## 11. Controllers and requests

Controllers should receive validated input, identify the authenticated actor, invoke an application/service/repository boundary, and produce the response. Decompose when a controller accumulates multiple independent workflows; line count alone is a review signal, not architecture truth.

Feature FormRequests belong to the feature. Validation, actor derivation, application authorization, ownership checks, and domain invariants must be defense in depth. UI visibility is not authorization.

## 12. Events and listeners

Use events for meaningful occurrences and optional reactions:

```text
Package A completes/defines an occurrence -> stable event -> Package B-owned listener reacts
```

The publisher owns the event name/class, minimal stable payload, timing, and transaction semantics. The consumer owns its listener. Foundation must not own a listener that exists only for an optional package.

Do not use events as random function calls or hide a required synchronous capability behind an optional listener. For `feature.entity.action.before/after`, `before` means the operation has not completed; `after` is emitted only after the defined successful state. State whether dispatch occurs inside or after a database transaction. Cross-package payloads are public extension API and require compatibility consideration.

## 13. View render events and UI contributions

Core's `view_render_event()` dispatches a named Laravel event with a `ViewRenderEventManager`; listeners add templates, and the manager renders them with supplied parameters. Use it when an optional package contributes presentation to a generic page.

```text
Generic host view -> feature-neutral hook <- package-owned listener and fragment
```

The host must not include `event::...`, call feature routes, inspect feature permissions, query feature tables, or discover feature presence. The package owns its fragment, query preparation, labels, and authorization.

## 14. Generic registries

Generic registries are appropriate for Dashboard metrics, Mega Search tabs, Quick Creation actions, navigation, generic page actions, and query contributions when the abstraction remains meaningful without any one optional package.

Good APIs describe the host concept: `registerMetric`, `registerSearchTab`, `registerAction`. APIs such as `registerEventMetric` or `registerStudentSearch` embed feature knowledge and are forbidden. Contributions originate from the optional package provider and keep feature queries inside that package.

## 15. Routes, ACL, menu, config, and presentation

- Every optional package owns its route files and actions. Foundation route files must not require optional routes.
- Packages use generic host middleware (`web`, `user`, `admin_locale`, student authentication, Bouncer) as applicable.
- Every optional package owns its ACL definitions; Foundation owns ACL aggregation/enforcement.
- Every optional package owns its menu entries; Admin owns menu aggregation/rendering.
- Feature configuration—including fields displayed in a generic configuration page—belongs to the feature.
- Feature controllers, requests, DataGrids, views, breadcrumbs, notifications, and static translations remain in the feature package.
- A package must not store another package's page merely because it supplies the shell.
- Cross-package controller calls are forbidden; call a deliberate application/service contract instead.

## 16. Localization

Feature static strings use the feature translation namespace and preserve the package's shipped-locale key parity. Business records never belong in language files. Content localization uses the generic Locale/content infrastructure and the classification, write, read, fallback, direction, and completeness rules in Rule 06. Core localization must not know feature-specific translatable models.

## 17. Migrations and foreign keys

Feature tables belong to package migrations. An optional table may reference Foundation. Optional-to-optional foreign keys require an explicit dependency plus installation, removal, deletion, and retained-history policy. Foundation tables must not reference optional tables unless the architectural classification changes.

Historical migrations required to reproduce supported database history are not automatically legacy residue. Do not rewrite an already-released migration merely to make the directory look cleaner; remove genuinely obsolete unpublished migrations only with evidence and an approved migration strategy.

## 18. Optional dependencies and interaction classification

Before Package A interacts with Package B, classify it:

1. `REQUIRED_SYNCHRONOUS_CAPABILITY` — declare B and call a stable Contract/Service API;
2. `OPTIONAL_REACTION` — listen to B's stable event;
3. `MODEL_EXTENSION` — use deliberate Concord replacement;
4. `RELATION_EXTENSION` — register a runtime relation from A;
5. `UI_CONTRIBUTION` — use a generic render hook/registry;
6. `QUERY_CONTRIBUTION` — use a generic registry while A owns its query.

No optional model, repository, or service may be imported without declaring its package dependency. Do not introduce reverse source knowledge merely because one package extends another.

## 19. Package public API

Another package must not freely import arbitrary internals. Deliberate public integration surfaces may include Contracts, application services, established repositories, published events, Proxies, DTOs, and contribution interfaces. Controllers, FormRequests, Blade views, migrations, and internal helpers are not cross-package APIs.

Required synchronous APIs should be stable, narrowly scoped, container-injectable, and tested from the consumer boundary.

## 20. Original package immutability

Do not edit an original package solely to support an extension when Concord, events/listeners, runtime relations, generic registries, view render events, or service contracts express the requirement cleanly.

If no adequate mechanism exists, a small generic extension point may be added to the original package only when it is feature-neutral, useful without the extension, minimal, documented, and tested. Immutability must not be used to justify reflection, feature checks, dynamic service lookup, or unnecessary frameworks.

## 21. No legacy residue

A migration/remediation is incomplete while unnecessary residue remains, including:

- old namespaces, controllers, routes, views, translation keys, ACL/menu definitions, or config;
- deprecated aliases, class aliases, wrappers, temporary adapters, or duplicate implementations;
- dead helpers, repositories, services, providers, tests, or Composer mappings;
- commented-out old code or migration-era TODO/comments;
- feature behavior left in Foundation;
- tests that preserve obsolete locations rather than current behavior.

Renaming old files, wrapping the old implementation, or leaving new and old implementations together is fake cleanup. An exception requires a documented external backward-compatibility contract, owner, supported lifetime, and removal plan.

Before deletion, search namespace references, route actions, container bindings, views, config, tests, Composer autoload, and runtime registration. Preserve required migration history and data policy. Permanent documentation describes current architecture; historical reports belong elsewhere.

## 22. Forbidden dependency evasions

Do not manage architecture through `class_exists`, optional-table `Schema::hasTable`, feature-detection `Route::has`, string service locators, reflection/namespace/filesystem discovery, catch-missing-class paths, global feature helpers, or silent aliases. Explicit composition and declared dependencies are required.

## 23. Package removability and installation

Installing a package may require supported root composition registration: provider, Concord module, Composer package/mapping, caches/autoload, and package-specific data installation. It must not require adding feature business logic to Foundation.

Uninstallation consists conceptually of disabling/unregistering composition, applying the package's explicit data-retention/removal policy, removing code, and refreshing autoload/cache. The target is:

```text
FOUNDATION_SOURCE_EDITS_REQUIRED_TO_REMOVE: 0
```

Provider-disabled, formally unregistered, and physically absent are separate verification states. Test removal only in an isolated copy/fixture/CI environment; otherwise report `NOT_RUN`.

## 24. Required package architecture manifest

Every optional/extension package must maintain an auditable record:

```text
PACKAGE:
<name>

TYPE:
FOUNDATION | OPTIONAL_FEATURE | EXTENSION

FOUNDATION_DEPENDENCIES:
<exact>

OPTIONAL_DEPENDENCIES:
<exact or NONE>

MAIN_PROVIDER:
<exact>

MODULE_PROVIDER:
<exact or N/A>

EVENT_PROVIDER:
<exact or N/A>

PUBLIC_CONTRACTS:
<exact or N/A>

MODEL_REPLACEMENTS:
<exact or N/A>

RUNTIME_RELATION_EXTENSIONS:
<exact or N/A>

PUBLISHED_EVENTS:
<exact or N/A>

LISTENED_EVENTS:
<exact or N/A>

UI_EXTENSION_POINTS_USED:
<exact or N/A>

ROUTES / ACL / MENU / CONFIG / MIGRATIONS:
<exact ownership>

ADMIN_PRESENTATION / FRONTEND_PRESENTATION:
<exact ownership>

DATA INSTALL/UNINSTALL POLICY:
<exact>

FOUNDATION_SOURCE_EDITS_REQUIRED_TO_REMOVE:
0
```

The manifest must reflect source evidence. A package `composer.json` with an empty `require` block does not declare an optional dependency merely because root autoload happens to make the namespace available.

## 25. Creation and completion gates

Before implementation:

1. classify the package;
2. declare Foundation and optional dependencies;
3. assign ownership for every surface;
4. define the public integration API;
5. choose extension mechanisms by interaction type;
6. define persistence, authorization, UI, localization, and removal policy;
7. complete Rule 06's localization/model classification when applicable.

Before completion:

1. verify self-ownership and dependency direction;
2. verify no Foundation leakage or hidden optional dependency;
3. verify authorization and domain invariants independently of UI;
4. verify routes, provider registration, translations, migrations, and applicable Concord bindings;
5. add robust architecture and behavior tests without fragile word matching;
6. prove one authoritative implementation and `NO_LEGACY_RESIDUE`;
7. document the package manifest and exact removal-test state;
8. pass focused and repository-level validation appropriate to the risk.

Current Event and LostAndFound code provide useful examples of package-owned resources and composition. They are references, not blanket declarations that every current detail is compliant. Existing Student and Shop ownership leakage must be remediated under this rule in later authorized waves.
