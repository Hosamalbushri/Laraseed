# LARASEED PACKAGE GENERATOR V2 — STEP 01: DECLARATIVE CAPABILITY ARCHITECTURE AUDIT

**Document Type:** Architectural Audit & Declarative Capability Specification  
**Status:** DRAFT / AUDIT ONLY (No source code or tests modified)  
**Target Identifier:** `V2-ARCH-001`  
**Date:** October 2026  
**Author:** Laraseed Architecture Team  

---

## 1. Executive Summary & Audit Context

### 1.1 Objective
The purpose of this audit is to resolve technical debt **`V2-ARCH-001`**: eliminating dynamic, runtime reflection probing (specifically `class_exists(AdminServiceProvider::class)`) in generated service providers by designing a deterministic, **Declarative Capability Architecture** for the Laraseed Package Ecosystem.

### 1.2 Strict Audit Scope
In adherence to the design-only mandate:
- **Zero source code modifications** have been made.
- **Zero test modifications** have been made.
- **Zero migration or generator changes** have been applied.
- The V1 baseline remains completely stable and intact (182 passing tests, 1658 assertions, 67 Foundation routes).

### 1.3 The Technical Debt (`V2-ARCH-001`)
In Laraseed Package Generator V1, optional packages generated with `laraseed:make-package` included the following registration logic inside `src/Providers/PackageServiceProvider.php`:

```php
// Technical Debt V2-ARCH-001 in V1 PackageServiceProvider::register()
$adminProvider = '\\' . __NAMESPACE__ . '\\..\\Admin\\Providers\\AdminServiceProvider';
if (class_exists($adminProvider)) {
    $this->app->register($adminProvider);
}
```

While functional as a transitional mechanism in V1, runtime class probing violates key architectural invariants:
1. **Implicit Coupling:** The primary service provider assumes a rigid, unconfigured sub-namespace convention.
2. **Runtime Overhead & Side Effects:** `class_exists()` triggers Composer's PSR-4 class loader on every application boot, even when the package does not provide or require an Admin UI.
3. **No Static Introspection:** Foundation's `OptionalPackageManifestLoader` and diagnostic tooling cannot know what capabilities a package offers without executing or reflecting on the code.
4. **Environment Blindness:** Headless CLI workers, API microservices, and decoupled runtimes cannot selectively load or disable individual package layers (e.g. Admin UI vs. Core Business Logic) because loading is governed by file presence rather than declarative intent.

---

## 2. Forensic Audit of Current V1 Architecture & Lifecycle

### 2.1 Manifest Loading & Composition Flow
The existing package composition pipeline in Laraseed Foundation operates in three distinct phases:

```
+------------------------------------+
|  1. Manifest Compilation           |
|  OptionalPackageManifestLoader     |
|  Reads extra.laraseed in JSON      |
+-----------------+------------------+
                  |
                  v
+-----------------+------------------+
|  2. Composition & Sorting          |
|  OptionalPackageComposition        |
|  Validates DAG, Sorts Enabled Pkgs |
+-----------------+------------------+
                  |
                  v
+-----------------+------------------+
|  3. Application Bootstrapping      |
|  - bootstrap/providers.php         |
|  - config/concord.php              |
|  - config/laraseed.php             |
+------------------------------------+
```

#### Manifest Schema in V1:
```json
{
    "name": "vendor/package-name",
    "type": "library",
    "extra": {
        "laraseed": {
            "id": "package_name",
            "type": "optional",
            "provider": "Vendor\\PackageName\\Providers\\PackageServiceProvider",
            "concord_module": "Vendor\\PackageName\\Providers\\ModuleServiceProvider"
        }
    }
}
```

#### Validation Invariants in `OptionalPackageManifestLoader`:
- `id`: Lowercase alphanumeric snake_case (`/^[a-z][a-z0-9_]*$/`).
- `type`: Must strictly equal `"optional"`.
- `provider`: Must be a valid class string subclassing `Illuminate\Support\ServiceProvider`. Verified via `class_exists` at manifest compilation time.
- `concord_module`: Nullable; if present, must subclass `Konekt\Concord\BaseModuleServiceProvider`.
- `requires`: Computed from Composer `require` block intersecting with known optional package composer names.

### 2.2 Bootstrapping Behavior & Discovery Patterns
A forensic grep across the entire codebase revealed the following resource discovery patterns:

| Pattern | Location | Classification | Assessment |
| :--- | :--- | :--- | :--- |
| `class_exists($adminProvider)` | `provider.php.stub` | **Runtime Probing (Debt)** | **Target for removal (`V2-ARCH-001`)**. Needs declarative replacement. |
| `class_exists($eventProvider)` | `provider.php.stub` | **Runtime Probing (Debt)** | Should be declarative or registered via Laravel's native event discovery. |
| `is_dir(Resources/lang)` | `provider.php.stub` | Safe Resource Boot | Normal package-internal asset check for existing filesystem directory. |
| `is_dir(Resources/views)` | `provider.php.stub` | Safe Resource Boot | Normal package-internal asset check for existing filesystem directory. |
| `file_exists(Routes/web.php)` | `provider.php.stub` | Safe Resource Boot | Normal package-internal route check. |
| `is_dir(Database/Migrations)` | `provider.php.stub` | Safe Resource Boot | Normal package-internal migration loader check. |
| `glob(Console/Commands/*.php)` | `provider.php.stub` | Console Auto-Registration | Safe console-only command discovery (only runs when `runningInConsole()`). |
| `class_exists($provider)` | `OptionalPackageManifestLoader` | Static Preflight | Safe preflight assertion during manifest compilation. |

---

## 3. Declarative Capability Schema Design & Evaluation

To eliminate `V2-ARCH-001`, three design options were evaluated for the package manifest schema (`composer.json` -> `extra.laraseed`).

### 3.1 Option A: Flat Provider Keys
```json
{
    "extra": {
        "laraseed": {
            "id": "customer_portal",
            "type": "optional",
            "provider": "Vendor\\CustomerPortal\\Providers\\CustomerPortalServiceProvider",
            "admin_provider": "Vendor\\CustomerPortal\\Admin\\Providers\\AdminServiceProvider",
            "concord_module": "Vendor\\CustomerPortal\\Providers\\ModuleServiceProvider"
        }
    }
}
```

- **Pros:** Minimal syntax addition, simple schema.
- **Cons:**
  - Hardcodes the concept of "Admin" directly into the core manifest schema.
  - Not extensible: adding `api_provider`, `cli_provider`, `webhook_provider`, etc. causes schema bloat in Foundation.
  - Cannot associate metadata (e.g. enabled status, middleware, routes) with a capability.
- **Verdict:** **REJECTED**. Violates the Foundation-Decoupling invariant.

---

### 3.2 Option B: Providers Dictionary
```json
{
    "extra": {
        "laraseed": {
            "id": "customer_portal",
            "type": "optional",
            "providers": {
                "runtime": "Vendor\\CustomerPortal\\Providers\\CustomerPortalServiceProvider",
                "admin": "Vendor\\CustomerPortal\\Admin\\Providers\\AdminServiceProvider"
            },
            "concord_module": "Vendor\\CustomerPortal\\Providers\\ModuleServiceProvider"
        }
    }
}
```

- **Pros:** Consolidates all providers in a single map.
- **Cons:**
  - Conflates low-level Service Providers with high-level architectural capabilities.
  - Breaks backward compatibility with existing V1 manifests that use `"provider": "..."`.
  - Does not distinguish between required base runtime providers and optional domain capabilities.
- **Verdict:** **REJECTED**.

---

### 3.3 Option C: First-Class Declarative Capabilities (RECOMMENDED)
```json
{
    "extra": {
        "laraseed": {
            "id": "customer_portal",
            "type": "optional",
            "provider": "Vendor\\CustomerPortal\\Providers\\CustomerPortalServiceProvider",
            "concord_module": "Vendor\\CustomerPortal\\Providers\\ModuleServiceProvider",
            "capabilities": {
                "admin": {
                    "provider": "Vendor\\CustomerPortal\\Admin\\Providers\\AdminServiceProvider",
                    "enabled": true
                },
                "api": {
                    "provider": "Vendor\\CustomerPortal\\Api\\Providers\\ApiServiceProvider",
                    "enabled": true
                }
            }
        }
    }
}
```

- **Pros:**
  1. **Strict Decoupling:** Foundation Core validates the generic structure of `capabilities` without needing to know specific domain semantics (Admin, API, DataGrid, Web).
  2. **100% Backward Compatible:** The top-level `provider` and `concord_module` keys remain standard for V1 compatibility; `capabilities` is an optional, additive dictionary.
  3. **Extensible:** Future capabilities (e.g. `reporting`, `webhooks`, `storefront`, `cli`) use the exact same declarative contract without modifying Foundation schema.
  4. **Deterministic Preflight Validation:** Manifest loader verifies capability provider classes at compile time, completely eliminating runtime `class_exists()` probes.
  5. **Selective Activation:** Enables headless or specialized environments to query `$composition->capabilityProviders('admin')` or selectively boot capabilities.
- **Verdict:** **ACCEPTED & RECOMMENDED**.

---

## 4. Formal Specification: RECOMMENDED_V2_MANIFEST

### 4.1 Schema Definition
The V2 Laraseed Package Manifest specification for `composer.json` is defined as:

```json
{
    "$schema": "https://json-schema.org/draft/2020-12/schema",
    "name": "vendor/package-name",
    "description": "Laraseed Optional Package Description",
    "type": "library",
    "license": "MIT",
    "autoload": {
        "psr-4": {
            "Vendor\\PackageName\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Vendor\\PackageName\\Tests\\": "tests/"
        }
    },
    "extra": {
        "laraseed": {
            "id": "package_name",
            "type": "optional",
            "provider": "Vendor\\PackageName\\Providers\\PackageServiceProvider",
            "concord_module": "Vendor\\PackageName\\Providers\\ModuleServiceProvider",
            "capabilities": {
                "admin": {
                    "provider": "Vendor\\PackageName\\Admin\\Providers\\AdminServiceProvider",
                    "enabled": true
                }
            }
        }
    }
}
```

### 4.2 Field Contracts & Invariants

| Field | Type | Required | Description & Validation Rules |
| :--- | :--- | :--- | :--- |
| `extra.laraseed.id` | `string` | **Yes** | Alphanumeric snake_case identifier matching `/^[a-z][a-z0-9_]*$/`. Unique across all catalog packages. |
| `extra.laraseed.type` | `string` | **Yes** | Must strictly equal `"optional"`. |
| `extra.laraseed.provider` | `class-string` | **Yes** | Primary runtime ServiceProvider extending `Illuminate\Support\ServiceProvider`. |
| `extra.laraseed.concord_module` | `class-string\|null` | No | Concord module provider extending `Konekt\Concord\BaseModuleServiceProvider`. |
| `extra.laraseed.capabilities` | `array<string, object>` | No | Map of capability name to capability configuration object. |
| `capabilities.<name>.provider` | `class-string` | **Yes** (if capability present) | Capability ServiceProvider extending `Illuminate\Support\ServiceProvider`. |
| `capabilities.<name>.enabled` | `bool` | No | Boolean flag indicating default capability state (defaults to `true`). |

---

## 5. Architectural Boundaries & Ownership Separation

### 5.1 Foundation Core Responsibilities (`packages/Webkul/Core`)
1. **Syntax & Schema Enforcement:** `OptionalPackageManifestLoader` parses `extra.laraseed.capabilities`, asserting:
   - Capability key matches `/^[a-z][a-z0-9_]*$/`.
   - `provider` is a non-empty string and a valid subclass of `Illuminate\Support\ServiceProvider`.
   - `class_exists($capabilityProvider)` succeeds during manifest loading.
2. **Catalog Query Interface:** `OptionalPackageComposition` exposes domain-agnostic inspection methods:
   - `hasCapability(string $packageId, string $capability): bool`
   - `capability(string $packageId, string $capability): ?array`
   - `capabilities(string $packageId): array`
   - `capabilityProviders(string $capability): list<string>` (returns all enabled providers for a given capability across all enabled packages).
3. **Foundation Neutrality:** Foundation Core does **NOT** know or care what the `"admin"` capability does. It treats capabilities as generic tagged providers.

### 5.2 Package-Level Responsibilities
1. **Self-Declaration:** The package explicitly lists its capabilities in `composer.json`.
2. **Clean Primary Provider:** The generated `PackageServiceProvider::register()` no longer contains `class_exists()` or references to `AdminServiceProvider`.
3. **Lifecycle Orchestration:**
   - **Mode A (Package-Orchestrated):** In standard operation, `PackageServiceProvider` registers its declared capability providers deterministically if configured.
   - **Mode B (Composition-Orchestrated):** Alternatively, Foundation's `OptionalPackageComposition::capabilityProviders('admin')` can be loaded directly by the Admin layer during admin bootstrap, allowing complete isolation of Admin code in non-admin contexts.

---

## 6. Static Preflight Validation Rules

During `OptionalPackageManifestLoader::load(array $manifestPaths)`, the following validation pipeline will execute:

```
[Read JSON Manifest]
         |
         v
[Validate extra.laraseed]
         |
         v
[Validate id, type, provider, concord_module]
         |
         v
[Validate extra.laraseed.capabilities (if present)]
    |--> Check is_array($capabilities)
    |--> For each $capName => $capConfig:
            |--> Assert $capName is valid slug (/^[a-z][a-z0-9_]*$/)
            |--> Assert is_array($capConfig)
            |--> Assert is_string($capConfig['provider'])
            |--> Assert class_exists($capConfig['provider'])
            |--> Assert is_subclass_of($capConfig['provider'], ServiceProvider::class)
            |--> Assert is_bool($capConfig['enabled'] ?? true)
         |
         v
[Build Deterministic Package Catalog with Validated Capabilities]
```

If any assertion fails, `OptionalPackageManifestLoader` immediately throws `Webkul\Core\Exceptions\InvalidPackageComposition` with an exact, diagnostic error message indicating the manifest file path, package ID, and the faulty capability definition.

---

## 7. Dependency Graph vs. Capability Graph Separation

### 7.1 Inter-Package Dependencies
- Dependencies between packages (e.g. `PackageB` requires `PackageA`) continue to be defined via Composer's native `"require"` block.
- `OptionalPackageComposition` enforces DAG acyclicity and verifies that all required packages are enabled in `LARASEED_OPTIONAL_PACKAGES`.

### 7.2 Intra-Package Capabilities
- Capabilities describe internal facets or integration surfaces of a single package.
- Capabilities do not create cyclical dependencies with Foundation or other optional packages.
- Cross-package capability extensions (e.g. `PackageB` adding a menu item under `PackageA`'s admin menu) are resolved via Laravel's native configuration merging (`menu.admin`), maintaining loose coupling.

---

## 8. Backward Compatibility & Migration Strategy

### 8.1 Zero-Breaking-Change Guarantee for V1
- Any package with a V1 manifest (having `provider` and `concord_module`, but no `capabilities` block) remains 100% valid.
- `OptionalPackageManifestLoader` defaults `capabilities` to an empty array `[]` when the key is omitted.
- No existing tests or deployments will break.

### 8.2 Safe Transitional Provider Registration
For packages transitioning from V1 to V2:
1. When generating new packages with `laraseed:make-package` in V2, `PackageServiceProvider` is clean: it does not probe with `class_exists()`.
2. When adding admin integration with `laraseed:make-admin Vendor/Package`, the command:
   - Generates the Admin skeleton in `src/Admin/`.
   - Automatically updates the package's `composer.json` to declare the `"admin"` capability under `extra.laraseed.capabilities`.
3. Legacy V1 packages retaining `class_exists()` in their custom `PackageServiceProvider` will continue to function without error, providing an orderly deprecation runway.

---

## 9. Generator Impact & Tooling Architecture (V2 Roadmap)

### 9.1 Impact Matrix on Generator Commands

| Generator Command | V1 Behavior | V2 Planned Evolution |
| :--- | :--- | :--- |
| `laraseed:make-package` | Generates `composer.json` with only `provider` & `concord_module`. Stub contains `class_exists` probe. | Generates pure manifest; `PackageServiceProvider` stub is completely free of `class_exists` probes. |
| `laraseed:make-admin` | Generates files in `src/Admin/`. Relies on `PackageServiceProvider` probing for `AdminServiceProvider`. | Generates files in `src/Admin/` AND injects/updates `"capabilities.admin"` in `composer.json`. |
| `laraseed:make-datagrid` | Generates DataGrid class in `src/DataGrids/` or `src/Admin/DataGrids/`. | No manifest change; integrates directly with declared Admin capability or package DataGrid layer. |
| `laraseed:make-route` | Generates web/api routes in `src/Routes/`. | Generates routes; can optionally register API capability if `--type=api` is isolated. |
| `laraseed:make-provider` | Generates standalone provider in `src/Providers/`. | Generates provider; supports `--capability=` option to register new declarative capability. |
| `laraseed:package-diagnostics` | Inspects package status based on class loading. | Enhanced to display full Declarative Capability matrix per package. |

---

## 10. Performance, Security, and Verification Invariants

### 10.1 Cache Optimization & Zero Probe Overhead
- Eliminating `class_exists()` removes thousands of redundant autoloader file stats across typical multi-package requests.
- When `php artisan config:cache` is executed, the entire composed capability catalog is frozen into Laravel's static configuration cache, resulting in **$O(1)$** capability resolution at runtime.

### 10.2 Security Boundaries
- Only classes explicitly declared in trusted local `composer.json` manifests are loaded.
- Strict class-existence and `ServiceProvider` subclass validation prevents arbitrary class instantiation or unintended code execution.

---

## 11. Implementation Roadmap for Subsequent Steps

```
+-------------------------------------------------------------------------------+
| V2 Step 01: Audit & Declarative Capability Architecture Design (COMPLETE)     |
|             Deliverable: docs/reports/LARASEED_PACKAGE_GENERATOR_V2_STEP_01.md|
+-------------------------------------------------------------------------------+
                                      |
                                      v
+-------------------------------------------------------------------------------+
| V2 Step 02: Foundation Runtime Manifest Loader & Composition Implementation   |
|             - Update OptionalPackageManifestLoader with capability parser.    |
|             - Update OptionalPackageComposition with capability queries.       |
|             - Comprehensive unit & feature tests for capability schema.       |
+-------------------------------------------------------------------------------+
                                      |
                                      v
+-------------------------------------------------------------------------------+
| V2 Step 03: Generator & Stub Modernization                                     |
|             - Update composer.json.stub & provider.php.stub (remove probes).  |
|             - Update AdminGenerator to declare capabilities in composer.json. |
|             - Add tests for declarative generator output.                     |
+-------------------------------------------------------------------------------+
                                      |
                                      v
+-------------------------------------------------------------------------------+
| V2 Step 04: End-to-End Verification, Diagnostic CLI & V2 Final Certification  |
|             - Generate real V2 test package with declared capabilities.       |
|             - Verify boot, isolation, disablement, and full test suite.       |
+-------------------------------------------------------------------------------+
```

---

## 12. Verification & Non-Interference Statement

- **Source Code Check:** `git status` confirms zero modifications to `packages/Webkul/`, `packages/Laraseed/`, `config/`, or `bootstrap/`.
- **Test Integrity:** All 182 tests and 1658 assertions remain in a 100% passing state.
- **V1 Baseline Preservation:** V1 package generator functionality remains completely uncompromised.
