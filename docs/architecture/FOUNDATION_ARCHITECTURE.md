# Laraseed Foundation Architecture

## Purpose

Laraseed Foundation is the reusable application seed beneath business capabilities. It supplies shared runtime, identity, administration, tabular presentation, installation, and localization infrastructure without requiring a specific business package or public frontend.

Foundation is deliberately small, self-contained, and modular.

## Classification

| Package | Classification | Development default | Build Pipeline |
| --- | --- | --- | --- |
| `Webkul\Core` | Foundation | Always enabled | PHP / Service Container |
| `Webkul\User` | Foundation | Always enabled | PHP / Eloquent / Auth |
| `Webkul\DataGrid` | Foundation | Always enabled | PHP / Query Builder / Export |
| `Webkul\Admin` | Foundation | Always enabled | Package-local Vite (`packages/Webkul/Admin`) |
| `Webkul\Installer` | Foundation | Always enabled | Package-local Vite (`packages/Webkul/Installer`) |

### Physically Removed Packages

The following legacy and feature packages have been intentionally removed and are NOT part of the current active architecture:

- `Webkul\Web` (Public site runtime and fallbacks — removed)
- `Webkul\Website` (Public presentation package — removed)
- `Webkul\Student` (Student portal and business domain — removed)
- `Webkul\LostAndFound` (Lost & Found domain — removed)
- `Webkul\Theme` (Legacy theme engine — removed)

The current Foundation contains **no public-facing frontend package** and **no default `/` route**. Requests to `/` return HTTP 404 until a dedicated presentation package is introduced.

## Foundation Responsibilities

- **Core:** Generic shared application infrastructure, persistence helpers, locale registry and services, optional package composition primitives (`OptionalPackageComposition`, `OptionalPackageManifestLoader`), and read-only diagnostic commands (`laraseed:packages`).
- **User:** Administrator identity, authentication guards (`admin`), roles, and authorization data.
- **Admin:** Authenticated administration control panel shell, UI components, and ACL/menu/configuration contribution hosts with an independent, self-contained package-local build pipeline.
- **DataGrid:** Generic query, filtering, tabular presentation, saved filters, and multi-format export infrastructure (CSV, XLS, XLSX).
- **Installer:** Application installation wizard, environment configuration, database seeding, and installation safety locks with an independent, self-contained package-local build pipeline.

## Proven Foundation Dependency Graph

The Foundation internal dependency graph is strictly acyclic and verified:

```text
Core

User      -> Core
DataGrid  -> Core
Installer -> Core, User
Admin     -> Core, DataGrid, User
```

- `Core` has zero internal package dependencies.
- `User` and `DataGrid` depend solely on `Core`.
- `Installer` depends on `Core` and `User`.
- `Admin` depends on `Core`, `DataGrid`, and `User`.
- None of the Foundation packages have dependencies on external business domains or removed packages.

## Optional Package Capability

The application retains a generic, high-safety Optional Package Composition engine inside `Webkul\Core`:
- Manifests declare metadata in `composer.json` under `extra.laraseed`.
- Composition is governed by `config/laraseed.php` and the `LARASEED_OPTIONAL_PACKAGES` environment variable.
- Default composition is Foundation only:
  ```dotenv
  LARASEED_OPTIONAL_PACKAGES=
  ```
- Diagnostics command `php artisan laraseed:packages` inspects loaded and registered optional packages without mutating application state.

## Certification Contract

Foundation integrity requires:
1. Foundation-only application boot clean without warnings or missing class errors.
2. Full test suite passing with 0 failures across Unit, Feature, and Composition suites.
3. Zero runtime or build references to removed packages (`Web`, `Website`, `Student`, `LostAndFound`, `Theme`).
4. Package-local Vite builds for `Admin` and `Installer` compile independently to production assets without cross-contamination.
