# Laraseed Package Generator V4 — Architecture & Internal Design

This document details the architectural principles, concurrency controls, discovery mechanisms, and security invariants implemented in Laraseed Package Generator V4.

---

## 1. Architectural Philosophy & Isolation

Laraseed Package Generator V4 is architected around strict encapsulation:
1. **Zero Core Pollution:** Code generated for optional packages lives entirely within `packages/{Vendor}/{Package}`. Core Foundation (`packages/Webkul/*`) and domain packages (`packages/Laraseed/Contacts/*`) are never directly mutated.
2. **Contract-First Extensibility:** Domain models leverage Concord contracts and proxies (`Contract`, `Proxy`), allowing downstream packages or host applications to override model implementations without altering package internals.
3. **Decoupled Presentation Capabilities:** Domain packages can exist with zero presentation logic. Admin (`src/Admin/`) and Web (`src/Web/`) capabilities are scaffolded as independently activatable sub-modules recorded in `composer.json`.

```mermaid
graph TD
    subgraph Root Application
        A[config/laraseed.php] --> B[Dynamic PSR-4 ClassLoader]
        A --> C[OptionalPackageComposition]
        C --> D[Concord Modules Registry]
        C --> E[Optional Package Providers]
    end

    subgraph Package Directory: packages/Vendor/Package
        F[composer.json] --> G[src/Providers/PackageServiceProvider]
        F --> H[extra.laraseed.capabilities]
        H --> I[AdminServiceProvider]
        H --> J[WebServiceProvider]
        G --> K[Models / Contracts / Proxies]
        G --> L[Http / Middleware / Routes]
        G --> M[Mail / Notifications / Events]
    end

    B -.->|Maps Namespace| G
    E -->|Loads Active Providers| I
    E -->|Loads Active Providers| J
```

---

## 2. Dual Package Architecture: Concord vs Plain

Laraseed V4 provides two distinct package modalities:

### 2.1 Concord Modular Packages (Default)
- **Target Use Case:** Full-featured modular extensions, business domains, entities, and presentation modules.
- **Scaffolding:** Includes `src/Providers/PackageServiceProvider.php` (extending `Konekt\Concord\BaseModuleServiceProvider`), `composer.json` with Concord extra metadata (`"type": "optional"`), and dedicated directories for models, migrations, repositories, and routes.

### 2.2 Plain PSR-4 Libraries (`--plain`)
- **Target Use Case:** Lightweight standalone PHP libraries, algorithmic helpers, SDKs, or standalone services.
- **Scaffolding:** Minimalist `composer.json` without Concord dependencies, clean `src/` directory, and a standard `Illuminate\Support\ServiceProvider`.

---

## 3. Filesystem Transactions & Concurrency Safety

### 3.1 Transactional Operations (`FilesystemTransaction`)
To guarantee that generation operations are atomic and never leave corrupt or partial files on disk:
- [`GenerationPlan`](file:///home/hosam/Documents/CampusHub-main/packages/Laraseed/PackageGenerator/src/Generators/GenerationPlan.php) compiles all target paths and planned contents.
- [`GenerationPlan::preflight()`](file:///home/hosam/Documents/CampusHub-main/packages/Laraseed/PackageGenerator/src/Generators/GenerationPlan.php) detects collisions prior to any disk mutation.
- [`FilesystemTransaction`](file:///home/hosam/Documents/CampusHub-main/packages/Laraseed/PackageGenerator/src/Generators/FilesystemTransaction.php) executes the plan. If any error occurs mid-stream, the transaction:
  1. Deletes all newly created files and directories.
  2. Restores all modified files (e.g. `composer.json`) to their exact original pre-transaction byte contents.

### 3.2 Cross-Process Concurrency Locking (`PackageLock`)
When multiple processes attempt to generate components or capabilities on the same package simultaneously (e.g. concurrent execution of `make-admin` and `make-web` in CI/CD or build pipelines):
- [`PackageLock`](file:///home/hosam/Documents/CampusHub-main/packages/Laraseed/PackageGenerator/src/Support/PackageLock.php) creates an advisory filesystem lock (`flock`) keyed by the canonical realpath of the target package.
- Lock files are stored under `storage/framework/locks/laraseed_pkg_{hash}.lock` and record PID and timestamp metadata.
- **Reentrant Support:** Nested lock requests from the same PHP process succeed without deadlocking.
- **Cross-Process Exclusion:** Concurrent processes wait up to a configurable timeout (default 5.0s) for the active transaction to finish before proceeding, guaranteeing zero lost updates to `composer.json`.

---

## 4. Package Discovery & Runtime PSR-4 Mapping

In Laravel 12, newly generated path packages are not indexed in Composer's static `autoload_psr4.php` until `composer dump-autoload` is executed.

To prevent bootstrap crashes (Catch-22 deadlocks where Artisan cannot run to manage packages), [`config/laraseed.php`](file:///home/hosam/Documents/CampusHub-main/config/laraseed.php) implements a dynamic classloading bridge:
1. Locates the active `Composer\Autoload\ClassLoader` from `spl_autoload_functions()`.
2. Discovers local `packages/*/*/composer.json` manifests.
3. Validates path containment (ensuring source paths reside strictly within `packages/`).
4. Dynamically registers the package's PSR-4 namespace prefix using `$composerLoader->addPsr4($prefix, $targetSrc)`.
5. **Partitioned Validation:**
   - **Active Packages:** Packages declared in `LARASEED_OPTIONAL_PACKAGES` are strictly loaded through `OptionalPackageManifestLoader`, raising descriptive diagnostic errors if required provider classes are missing.
   - **Inactive Packages:** Inactive packages on disk are checked with `class_exists()` and skipped if unresolvable, guaranteeing that broken dormant packages never halt host application bootstrap.

---

## 5. Security & Boundary Hardening

| Layer | Security Mechanism | Invariant Enforced |
| :--- | :--- | :--- |
| **Path Traversal** | [`PathGuard`](file:///home/hosam/Documents/CampusHub-main/packages/Laraseed/PackageGenerator/src/Support/PathGuard.php) | Rejects path escapes (`../`, `..\`, null bytes) in package names, class identifiers, template paths, and output targets. |
| **Symlink Containment** | Canonical Realpath Validation | Resolves canonical target paths via `realpath()`; targets pointing outside `packages/` are discarded. |
| **CSP Compliance** | Strict Blade Template Auditing | Web capability stubs contain zero inline JavaScript handlers (`onclick`), unescaped variable injections, or unsafe CSS sinks. |
| **Template Source Trust** | Dynamic Template Catalog | Custom templates registered via `WebTemplateCatalog` must specify trusted source paths residing within authorized package roots or application stubs. |
