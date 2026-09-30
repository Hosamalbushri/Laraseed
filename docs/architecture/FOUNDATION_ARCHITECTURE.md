# CampusHub Foundation Architecture

## Purpose

CampusHub Foundation is the reusable application seed beneath business capabilities. It supplies shared runtime, identity, administration, tabular presentation, installation, public Web presentation, themes, and localization infrastructure without requiring a campus business package.

Foundation is deliberately small. Generic infrastructure belongs here; Student, LostAndFound, and future business concepts do not.

## Classification

| Package or implementation | Classification | Development default |
| --- | --- | --- |
| `Webkul\Core` | Foundation | Always enabled |
| `Webkul\User` | Foundation | Always enabled |
| `Webkul\Admin` | Foundation | Always enabled |
| `Webkul\DataGrid` | Foundation | Always enabled |
| `Webkul\Installer` | Foundation | Always enabled |
| `Webkul\Web` | Foundation | Always enabled |
| `Webkul\Theme` | Foundation | Always enabled |
| `themes/base` (`Base`) | Theme implementation | Selected by default |
| `Webkul\Student` | Optional feature | Disabled |
| `Webkul\LostAndFound` | Optional feature | Disabled |

Base is the default Foundation-compatible presentation theme. It is not a business package and owns no application routes, business queries, or business policy.

Localization is infrastructure distributed across Foundation rather than a separate package: Core owns locale identity and content-locale services, Admin owns the employee interface locale and management surface, Web owns public request locale/context, and Installer owns only its installation-session locale.

## Foundation Responsibilities

- **Core:** generic shared application infrastructure, persistence helpers, locale services, configuration and extension primitives. It must not become a business dumping ground.
- **User:** employee/Admin identity, authentication, roles, groups, and authorization data. It does not represent arbitrary business users.
- **Admin:** generic authenticated administration shell and ACL/menu/configuration/contribution hosts. It is not a feature bucket.
- **DataGrid:** generic query, filtering, tabular presentation, saved-filter, and export infrastructure with no business ownership.
- **Installer:** Foundation installation and bootstrap infrastructure. It must install Foundation without Optional assumptions.
- **Web:** generic public-site runtime, context, navigation, sections, SEO, components, and root presentation with no business-domain knowledge.
- **Theme:** generic discovery, selection, resolution, inheritance, and view-override engine with no business logic.

## Proven Foundation Dependency Graph

The graph below is derived from the package Composer manifests. An arrow means “depends on.”

```text
Core

User      -> Core
DataGrid  -> Core
Installer -> Core, User
Admin     -> Core, DataGrid, User

Theme
Web       -> Core, Theme
```

Core and Theme have no internal package dependency. Foundation must never depend on Student, LostAndFound, or any future business package.

## Deployment Composition

`config/campushub.php` is the authoritative Optional-package deployment configuration. Package lifecycle metadata comes from each Optional package's `composer.json` under `extra.campushub`; Optional dependency edges come from Composer `require`.

`CAMPUSHUB_OPTIONAL_PACKAGES` is one comma-separated explicit selection. Main providers and Concord modules are generated atomically from the same validated state. Unknown IDs, missing dependencies, duplicate IDs, invalid provider/module classes, and dependency cycles fail before usable application boot. Dependencies are never silently enabled.

The Optional graph is:

```text
student        -> none
lost_and_found -> student
```

## Development Modes

Foundation development uses the repository default:

```dotenv
CAMPUSHUB_OPTIONAL_PACKAGES=
```

Optional-package work enables only the target and its explicit Optional dependencies:

```dotenv
# Student
CAMPUSHUB_OPTIONAL_PACKAGES=student

# LostAndFound
CAMPUSHUB_OPTIONAL_PACKAGES=student,lost_and_found
```

After changing composition, rebuild deployment caches together:

```bash
php artisan config:clear
php artisan route:clear
php artisan config:cache
php artisan route:cache
```

A cached composition intentionally takes precedence over later environment changes until caches are rebuilt.

## Optional Feature Freeze

During Foundation hardening, Student and LostAndFound are **available, disabled by default, and feature-development frozen**. Do not add new Optional features unless required to fix a Foundation regression or prove compatibility. The packages are not deprecated.

Disabling is not uninstalling. Disabled package source, migrations, existing schema, data, stored files, and migration history remain preserved. Destructive removal requires a separately authorized dependency-aware uninstall design.

## Certification Contract

Both modes are mandatory:

1. The repository-default Foundation-only composition must boot and pass its focused Core, User, Admin, DataGrid, Installer, Web, Theme/Base, localization, route-ownership, and Optional-absence coverage.
2. The full surviving application must pass with `student,lost_and_found` explicitly enabled.

Composition-matrix tests must continue to cover every valid dependency set and reject invalid sets. A passing all-enabled suite cannot replace Foundation-only proof, and Foundation-only proof cannot replace Optional compatibility.

Normative constraints remain in [Rule 08](../rules/08_SEED_FOUNDATION_AND_PACKAGE_ISOLATION_RULES.md); this document records the current architecture and development workflow.
