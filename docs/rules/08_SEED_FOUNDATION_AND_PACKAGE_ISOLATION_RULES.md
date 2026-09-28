# CampusHub Seed Foundation and Package Isolation Rules

> **Mandatory dependency-boundary standard.** CampusHub is a reusable seed. Foundation must remain useful without optional feature packages, and optional packages must compose onto Foundation without depositing feature behavior in it.

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

Student, Event, LostAndFound, Shop, Accounting, Inventory, HR, and future business packages are optional unless an explicit architectural decision reclassifies them.

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
