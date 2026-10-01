# Laraseed Seed Foundation and Package Isolation Rules

> **Mandatory dependency-boundary standard.** Laraseed is a reusable seed. Foundation must remain useful without optional feature packages, and optional packages must compose onto Foundation without depositing feature behavior in it.

## 1. Package classification

Every package must be classified before implementation:

- `FOUNDATION`: reusable infrastructure required by the seed;
- `OPTIONAL_FEATURE`: an installable business capability;
- `EXTENSION`: a package whose purpose is to extend another declared package.

The current Foundation boundary is:

```text
Webkul\Core
Webkul\Admin
Webkul\User
Webkul\DataGrid
Webkul\Installer
localization infrastructure
generic extension infrastructure
```

Student, LostAndFound, Shop, Accounting, Inventory, HR, and future business packages are optional unless an explicit architectural decision reclassifies them.

## 2. Dependency direction

```text
Foundation -> Optional Package: FORBIDDEN
Optional Package -> Foundation: ALLOWED
Optional Package A -> Optional Package B: EXPLICITLY DECLARED ONLY
```

Physical source ownership is authoritative. A file inside Admin is Foundation even when its class conceptually belongs to Student or Shop.

An optional dependency must be documented in the package architecture manifest and represented in supported package/composition metadata where the repository provides such metadata. Imports without a declaration are hidden dependencies.

## 3. Foundation owns infrastructure, not features

Foundation may own middleware aliases, authorization/menu/config aggregators, rendering shells, generic registries, localization services, DataGrid infrastructure, storage abstractions, and stable extension contracts. It must not own optional feature models, queries, controllers, routes, DataGrids, views, ACL/menu nodes, translations, configuration fields, migrations, listeners, or business services.

Admin is infrastructure. A page being displayed in Admin does not make its feature Admin-owned.

## 4. Package-owned registration

An optional package loads its own routes, views, translations, migrations, config, ACL/menu contributions, event listeners, and extension contributions from its provider or a delegated package-owned provider. Foundation must not require an optional route file, register an optional Concord module, merge optional ACL/menu/config, or load optional resources.

Root application composition metadata is an explicit exception. `bootstrap/providers.php`, `config/concord.php`, Composer mappings/package requirements, and equivalent supported manifests may name an optional package to install or enable it. Those files compose the application; they must not contain feature business implementation.

### 4.1 Optional integration ownership

- Foundation authentication configuration must not declare optional identities, guards, providers, or models. An optional identity package owns and contributes its authentication configuration during package registration.
- Foundation exception handling and guest-redirection code must not name an optional guard or route to an optional package. Foundation may expose a package-neutral resolver; each optional identity package owns its matching rule and destination.
- Foundation translations must not own optional business terminology.
- Foundation filesystem configuration must not define an optional package's disks. The optional package must merge its disk configuration while preserving established names, paths, and security semantics.
- Optional Admin and Web contributions must be registered by the optional package through generic Foundation extension points. Foundation hosts must not render optional routes, views, permissions, labels, or queries directly.
- Every package manifest must declare its physically proven internal package dependencies. Composer availability metadata does not replace runtime activation metadata or dependency validation.

## 5. Extension mechanisms

Choose the mechanism from the interaction:

| Interaction | Mechanism |
| --- | --- |
| Required synchronous capability | Declared dependency plus stable contract/service API |
| Optional reaction | Event published by owner, listener owned by consumer |
| Model substitution | Concord contract/model/proxy registration after collision analysis |
| Relation-only extension | Package-owned `resolveRelationUsing()` registration where appropriate |
| UI contribution | Generic view render hook or registry |
| Query/dashboard/search contribution | Generic contribution registry; feature query remains in feature |

Do not replace required behavior with an event merely to hide a dependency. Do not modify an original package to add reverse knowledge when the extension package can register the relation, listener, replacement, or UI contribution itself.

## 6. No fake isolation

The following are forbidden as optional-package discovery or coupling concealment in Foundation:

- `class_exists(OptionalPackage::class)`;
- `Schema::hasTable()` checks for optional feature tables;
- `Route::has()` checks for optional feature routes;
- `app('Webkul\\Optional\\...')` string service locators;
- reflection, namespace scanning, or filesystem discovery;
- dynamic class strings, catch-missing-class paths, aliases, wrappers, or duplicated implementations.

Generic Laravel events, the Event facade, and feature-neutral render events are not violations. Architecture tests must target package identities and proven feature identifiers, not fragile occurrences of words such as “event”.

## 7. Persistence direction

Feature tables and migrations belong to the feature package.

```text
Optional table -> Foundation table: allowed
Optional A table -> Optional B table: allowed only with declared dependency/lifecycle analysis
Foundation table -> Optional table: forbidden unless package classification changes
```

Foreign keys do not by themselves define a complete package lifecycle. Installation order, uninstallation/data policy, deletion semantics, and retained history must be explicit.

## 8. Static enforcement

Foundation architecture tests should recursively scan all Foundation source, not only known legacy directories. Where robustly detectable, guard against:

- optional namespaces;
- feature tables and route names;
- feature controllers, DataGrids, views, ACL/menu/config, and translations;
- hidden dependency-evasion patterns.

Formal composition manifests must be tested as explicit exceptions, never ignored globally.

## 9. Removability

An isolated optional package must be formally unregisterable and physically removable without editing Foundation source:

```text
FOUNDATION_SOURCE_EDITS_REQUIRED_TO_REMOVE_PACKAGE: 0
```

Provider-disabled, formally unregistered, and physically absent are three different states and must be reported separately. Formal removal includes provider, Concord, Composer, cache/autoload, and package-data policy as applicable.

Never modify and restore a user's live worktree to simulate removal. Use an isolated copy, temporary environment, or CI fixture; otherwise report `NOT_RUN`.

## 10. Completion gate

Isolation is complete only when dependency direction, physical ownership, package registration, runtime behavior, authorization, static guards, and no-legacy-residue checks pass. A passing functional suite alone does not waive an architectural violation.

## 11. Deployment composition

Foundation packages are always composed and must never be runtime-toggleable. Optional package enablement has one authoritative deployment configuration; environment values may feed that configuration, but application code must read the compiled configuration state rather than the environment directly.

Optional main providers and Concord modules must derive from the same validated package state so activation is atomic. Optional dependencies must be validated before usable application boot, and registration order must place dependencies before their consumers. Unknown package IDs, duplicate IDs, missing provider/module classes, invalid dependency combinations, and dependency cycles must fail with a clear configuration exception rather than a later class, route, binding, or database failure.

Composition is fixed for an application boot. Database-driven package state, request-time mutation, arbitrary package discovery, and runtime hot loading are forbidden unless a later explicit architecture decision replaces this deployment model. Configuration caching must preserve the selected composition.

Disabling a package unregisters its providers, Concord modules, routes, resources, bindings, listeners, configuration contributions, and migration discovery. It does not delete package code, schemas, data, stored files, or migration history: `DISABLE != UNINSTALL`.

Foundation tests must pass with every Optional package disabled. Optional-package tests must explicitly compose their declared dependency set where the test environment is not all-enabled.

## 12. Foundation-hardening development baseline

Foundation-only is the canonical development baseline while Foundation certification is in progress. Repository defaults must compose no Optional packages; Student and LostAndFound remain available, data-preserving, and disabled by default.

Optional feature development is frozen during this hardening period. Do not add Student or LostAndFound features unless a change is required to correct a Foundation regression or prove compatibility. This freeze is neither deprecation nor uninstall authorization, and Optional production code, schema, data, and files must remain intact.

Two test modes are permanent requirements: Foundation-only default certification and explicit all-enabled compatibility. A developer working on an Optional package enables only that package and its declared Optional dependencies; dependencies must never be silently auto-enabled. Configuration and route caches must be rebuilt whenever the selected deployment composition changes.

## 13. Optional package self-containment laws

- **PKG-SC-01 (Production Code Locality):** Every Optional Package must physically own 100% of its feature-specific production code in its own directory (`packages/Webkul/<Package>/src`).
- **PKG-SC-02 (Test Suite Locality):** Every Optional Package must physically own its feature-specific tests in its own directory (`packages/Webkul/<Package>/tests`). Feature and Unit tests specific to an Optional Package must never be stranded in central root `tests/`.
- **PKG-SC-03 (Resource Locality):** Optional packages must own their migrations, routes, ACL, menu, translations, views, DataGrids, configuration definitions, test fixtures, and development mocks.
- **PKG-SC-04 (Foundation Source Purity):** Foundation packages (`Core`, `Admin`, `User`, `DataGrid`, `Installer`, `Web`, `Theme`) must NEVER import Optional Package production classes or assume their existence.
- **PKG-SC-05 (Explicit Dependency Declaration):** An Optional Package must not depend on another Optional Package unless explicitly declared in package composition metadata (`extra.laraseed.requires` in `composer.json`).
- **PKG-SC-06 (Strict Dependency Directionality):** Dependencies between Optional Packages must remain strictly directional. A dependency must never silently become bidirectional (e.g. `LostAndFound -> Student` is permitted; `Student -> LostAndFound` is strictly 0).
- **PKG-SC-07 (Consumer Ownership of Integrations):** The consumer package owns cross-package listeners, extensions, and integration points. If package A extends package B, package A owns the integration, and package B must function completely in the absence of package A.
- **PKG-SC-08 (Clean Disablement via Provider Composition):** Disabling an Optional Package must remove all of its runtime contributions (routes, ACL, menu, models, storage disks, view hints) naturally through provider composition without leaving runtime errors.
- **PKG-SC-09 (Database Preservation During Deletion):** Deleting or disabling an Optional Package must never require destructive runtime database operations (`DROP TABLE` or rollback) against production or development databases.
- **PKG-SC-10 (Test Portability):** When an Optional Package is physically removed, all of its tests must disappear with the package, leaving zero broken or orphaned tests in root `tests/`.
- **PKG-SC-11 (Root Test Scope Restriction):** Root `tests/` may only test Foundation capabilities, multi-package composition metadata, and negative architectural isolation guards. Root tests must never test Optional Package business behavior.
- **PKG-SC-12 (Multi-Composition Matrix Certification):** Any change to Optional Packages or addition of a new package requires multi-composition matrix certification across all supported states (Foundation-only, Package-isolated, All-enabled, and Invalid-dependency rejection).

## 14. Future optional package acceptance contract

Every new or refactored Optional Package must satisfy this checklist before completion:

```text
[ ] Package metadata: composer.json contains id, type, provider, concord_module, and requires under extra.laraseed.
[ ] Explicit dependencies: all required optional packages are explicitly listed in requires.
[ ] Production code locality: 100% of feature code resides in packages/Webkul/<Package>/src/.
[ ] Routes locality: all feature routes reside in packages/Webkul/<Package>/src/Routes/.
[ ] Migrations locality: all feature migrations reside in packages/Webkul/<Package>/src/Database/Migrations/.
[ ] ACL locality: permissions are defined in packages/Webkul/<Package>/src/Config/acl.php.
[ ] Menu locality: menu items are defined in packages/Webkul/<Package>/src/Config/menu.php.
[ ] Translations locality: translations reside in packages/Webkul/<Package>/src/Resources/lang/.
[ ] Views locality: Blade views reside in packages/Webkul/<Package>/src/Resources/views/.
[ ] DataGrids locality: DataGrids reside in packages/Webkul/<Package>/src/DataGrids/.
[ ] Package configuration locality: config files reside in packages/Webkul/<Package>/src/Config/.
[ ] Tests locality: all feature and unit tests reside in packages/Webkul/<Package>/tests/.
[ ] Fixtures & dev support locality: test fixtures and local mock servers reside in packages/Webkul/<Package>/tests/Fixtures/ or dev/.
[ ] Foundation purity: 0 imports of this package in Foundation packages (Core, Admin, User, DataGrid, Installer, Web, Theme).
[ ] Reverse dependencies absent: other independent optional packages have 0 dependencies on this package.
[ ] Disabled composition boots: application boots and passes Foundation tests when package is disabled.
[ ] Enabled composition boots: application boots, loads all routes, ACL, menu, and passes package tests.
[ ] Invalid dependency fails fast: enabling this package without its declared dependencies throws InvalidPackageComposition.
[ ] Discovery by root runner: package testsuite is configured in phpunit.xml and discovered by vendor/bin/pest --testsuite=<Package>.
[ ] Deletion contract documented: step-by-step physical removal procedure is documented without database destruction.
```

## 15. Physical package deletion contracts

### 15.1 LostAndFound Deletion Procedure
```text
1. Disable lost_and_found from active composition (remove from LARASEED_OPTIONAL_PACKAGES).
2. Remove manifest catalog entry in config/laraseed.php (base_path('packages/Webkul/LostAndFound/composer.json')).
3. Remove root Composer PSR-4 mapping ("Webkul\\LostAndFound\\": "packages/Webkul/LostAndFound/src") in composer.json.
4. Remove directory packages/Webkul/LostAndFound/.
5. Run composer dump-autoload.
6. Rebuild or clear configuration/route caches (php artisan config:clear && php artisan route:clear).
7. Run Foundation + Student certification (LARASEED_OPTIONAL_PACKAGES=student vendor/bin/pest).
```

### 15.2 Student Deletion Procedure
```text
1. Follow Procedure 15.1 to remove LostAndFound first (LostAndFound requires Student).
2. Disable student from active composition (remove from LARASEED_OPTIONAL_PACKAGES).
3. Remove manifest catalog entry in config/laraseed.php (base_path('packages/Webkul/Student/composer.json')).
4. Remove root Composer PSR-4 mapping ("Webkul\\Student\\": "packages/Webkul/Student/src") in composer.json.
5. Remove directory packages/Webkul/Student/.
6. Run composer dump-autoload.
7. Rebuild or clear configuration/route caches.
8. Run Foundation-only certification (LARASEED_OPTIONAL_PACKAGES= vendor/bin/pest).
```

## 16. Central package registration and portability laws

- **PKG-REG-01 (Implementation Purity):** Package business implementation must never be stored in central registration files (`composer.json`, `config/laraseed.php`, `phpunit.xml`).
- **PKG-REG-02 (Metadata Restriction):** Central package registration is allowed only for installation, composition, autoloading, and test-runner metadata.
- **PKG-REG-03 (Dependency Authority):** Package dependency metadata has one authoritative owner: the package's own manifest (`packages/Webkul/<Package>/composer.json`).
- **PKG-REG-04 (Deterministic Discovery):** Installed-package discovery must remain deterministic, explicit, and build-time validated.
- **PKG-REG-05 (Filesystem Scanning Prohibition):** Runtime filesystem scanning (`glob`, `scandir`, recursive directory search) for package discovery is strictly forbidden.
- **PKG-REG-06 (Composer Invariance on Disablement):** Disabling an installed package must not require mutating Composer configuration or regenerating autoloader files.
- **PKG-REG-07 (Foundation Source Invariance on Deletion):** Physical package deletion may require build-time registration cleanup, but Foundation source edits remain strictly zero.
