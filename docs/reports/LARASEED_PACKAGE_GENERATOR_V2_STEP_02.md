# LARASEED PACKAGE GENERATOR V2 — STEP 02: CAPABILITY MANIFEST LOADER & COMPOSITION CONTRACT

**Document Type:** Foundation Implementation & Verification Report  
**Status:** COMPLETE / VERIFIED  
**Milestone:** V2 Step 02 (Declarative Capability Loader & Composition Contract)  
**Date:** October 2026  
**Author:** Laraseed Architecture Team  

---

## 1. Pre-Implementation Findings & Verification

Prior to implementation, a forensic examination was conducted on:
- `Webkul\Core\Packages\OptionalPackageManifestLoader`
- `Webkul\Core\Packages\OptionalPackageComposition`
- `Webkul\Core\Exceptions\InvalidPackageComposition`
- `config/laraseed.php`
- `bootstrap/providers.php`
- `config/concord.php`

### Confirmed Invariants
1. `OptionalPackageManifestLoader` compiles local `composer.json` files and parses `extra.laraseed`.
2. `OptionalPackageComposition` builds the directed acyclic graph (DAG), asserts acyclicity and required dependencies, and topologically sorts enabled packages.
3. No generator stubs, commands, or runtime provider registrations were modified in Step 02, ensuring strict separation between contract establishment and consumer booting.

---

## 2. Exact Manifest Schema Implemented

The declarative capability schema is fully integrated as an optional, backward-compatible extension to `extra.laraseed`:

```json
{
    "name": "vendor/package-name",
    "type": "library",
    "extra": {
        "laraseed": {
            "id": "blog",
            "type": "optional",
            "provider": "Vendor\\Blog\\Providers\\BlogServiceProvider",
            "concord_module": "Vendor\\Blog\\Providers\\ModuleServiceProvider",
            "capabilities": {
                "admin": {
                    "provider": "Vendor\\Blog\\Admin\\Providers\\AdminServiceProvider",
                    "enabled": true
                },
                "api": {
                    "provider": "Vendor\\Blog\\Api\\Providers\\ApiServiceProvider",
                    "enabled": false
                }
            }
        }
    }
}
```

### Invariants:
- **`capabilities` Container:** Optional associative array (defaults to `[]` when omitted).
- **Capability Key:** Must match regex `/^[a-z][a-z0-9_]*$/`.
- **Capability Object:**
  - `provider` (*Required*, `string`): Valid class string subclassing `Illuminate\Support\ServiceProvider`. Verified via `class_exists()` and `is_subclass_of()`.
  - `enabled` (*Optional*, `bool`): Defaults to `true`.
- **Unknown Fields Policy:** Strictly rejected. Any unrecognized key (e.g. `middleware`, `routes`, `extra_key`) immediately triggers `InvalidPackageComposition`.

---

## 3. Validation Rules & Enforcement Pipeline

During manifest compilation via `OptionalPackageManifestLoader::load()`, the following checks are executed in order:

```
[extra.laraseed.capabilities validation]
  ├── Assert is_array($rawCapabilities)
  └── For each $capName => $capConfig:
        ├── Assert preg_match('/^[a-z][a-z0-9_]*$/', $capName) === 1
        ├── Assert is_array($capConfig)
        ├── Assert array_diff(keys($capConfig), ['provider', 'enabled']) === [] (No unknown fields)
        ├── Assert array_key_exists('provider', $capConfig)
        ├── Assert is_string($provider) && $provider !== ''
        ├── Assert class_exists($provider)
        ├── Assert is_subclass_of($provider, ServiceProvider::class)
        └── Assert is_bool($enabled ?? true)
```

---

## 4. Declared vs. Active Capability Semantics

The architecture strictly distinguishes between **declared metadata** (catalog level) and **active runtime providers** (composition level):

1. **Declared Capability (`hasCapability`, `capability`, `capabilities`):**
   - Refers to capabilities declared in the package manifest.
   - `hasCapability('blog', 'admin')` returns `true` even if `enabled: false`.
   - `capability('blog', 'admin')` returns `['provider' => '...', 'enabled' => false]`.
   - Allows inspection, tooling, and diagnostic reflection of what features the package contains.

2. **Active Capability Provider (`capabilityProviders`):**
   - Refers to capability providers that should actively boot.
   - `capabilityProviders('admin')` only includes providers where the package is enabled **and** the capability has `enabled: true`.

---

## 5. Package-Enabled vs. Capability-Enabled Matrix (4 Quadrants)

The four operational states were formally implemented and verified via unit tests:

| Quadrant | Package State (in `LARASEED_OPTIONAL_PACKAGES`) | Manifest Capability State (`enabled`) | `hasCapability()` | `capabilityProviders()` Result |
| :--- | :--- | :--- | :--- | :--- |
| **Q1** | **Enabled (ON)** | **Enabled (`true`)** | `true` | **Included (Active)** |
| **Q2** | **Enabled (ON)** | **Disabled (`false`)** | `true` | *Excluded (Inactive)* |
| **Q3** | *Disabled (OFF)* | **Enabled (`true`)** | `true` | *Excluded (Inactive)* |
| **Q4** | *Disabled (OFF)* | **Disabled (`false`)** | `true` | *Excluded (Inactive)* |

---

## 6. OptionalPackageComposition API

The following public inspection API was added to `Webkul\Core\Packages\OptionalPackageComposition`:

```php
/**
 * Determine if a package declares a specific capability.
 */
public function hasCapability(string $packageId, string $capability): bool;

/**
 * Get the declared capability configuration for a package, or null if undeclared.
 *
 * @return array{provider: string, enabled: bool}|null
 */
public function capability(string $packageId, string $capability): ?array;

/**
 * Get all declared capabilities for a package.
 *
 * @return array<string, array{provider: string, enabled: bool}>
 */
public function capabilities(string $packageId): array;

/**
 * Get active capability providers for all enabled packages in deterministic dependency order.
 *
 * @return list<string>
 */
public function capabilityProviders(string $capability): array;
```

---

## 7. Deterministic Dependency Ordering

When resolving active capability providers via `capabilityProviders($capability)`:
- Packages are traversed according to `$this->enabled` (which is topologically sorted by the dependency DAG).
- If `Package B` requires `Package A`, and both declare capability `"admin"`, `Package A`'s admin provider appears before `Package B`'s admin provider.
- No arbitrary alphabetical sorting is applied that would alter dependency-first loading.

---

## 8. Duplicate Provider Policy

- If a package declares the same provider across multiple capabilities, or if multiple enabled packages share a provider class, `capabilityProviders()` applies `array_values(array_unique($providers))`.
- The first occurrence in topological dependency order is preserved, preventing duplicate service provider registration.

---

## 9. Error Diagnostics & Messages

All validation violations throw `Webkul\Core\Exceptions\InvalidPackageComposition` with clear, actionable messages:

- **Invalid Container:** `Optional package [blog] declares an invalid capabilities definition.`
- **Invalid Capability Name:** `Optional package [blog] declares an invalid capability name [admin-ui].`
- **Non-Object Definition:** `Optional package [blog] capability [admin] must be an object definition.`
- **Unknown Field:** `Optional package [blog] capability [admin] contains unknown field [middleware].`
- **Invalid Provider:** `Optional package [blog] capability [admin] declares an invalid provider class.`
- **Invalid Enabled Flag:** `Optional package [blog] capability [admin] declares an invalid enabled flag.`
- **Unknown Package Query:** `Unknown Optional package ID [unknown_pkg].`

---

## 10. Backward Compatibility Proof

- **V1 Manifests:** Existing manifests omitting `"capabilities"` load identically as before, with `capabilities` defaulting to `[]`.
- **Existing Tests:** 100% of existing tests pass without modification.
- **Runtime Non-Interference:** No capability booting was added to `bootstrap/providers.php` or `CoreServiceProvider` in this step.

---

## 11. Security Tests

The test suite explicitly tests and blocks:
- Path traversal attempts in capability names: `../admin` (Rejected)
- Slash notation: `admin/provider` (Rejected)
- Special characters / Uppercase: `Admin`, `_admin`, `1admin`, `admin.ui` (Rejected)
- Arbitrary non-ServiceProvider class injection (e.g. `stdClass`) (Rejected)
- Malformed data structures (e.g. nested arrays, unexpected integers, strings) (Rejected)

---

## 12. Complete Verification Results

### Test Suite Execution
```bash
./vendor/bin/pest
```
**Output:**
```text
Tests:    207 passed (1716 assertions)
Duration: 6.31s
```
*(Increased from 182 tests / 1658 assertions with 25 new capability test variations and 58 new assertions)*.

### Route Baseline Check
```bash
php artisan route:list
```
**Output:**
```text
Showing [67] routes
```
*(Foundation route count remains strictly at 67)*.

### Composer & Code Quality
- `composer validate --strict`: PASS (root and package generator composer.json files valid).
- `git diff --check`: PASS (zero whitespace issues).
- `php artisan laraseed:packages`: PASS (read-only output preserved).

---

## 13. Files Modified in Step 02

1. [`packages/Webkul/Core/src/Packages/OptionalPackageManifestLoader.php`](file:///home/hosam/Documents/CampusHub-main/packages/Webkul/Core/src/Packages/OptionalPackageManifestLoader.php)
2. [`packages/Webkul/Core/src/Packages/OptionalPackageComposition.php`](file:///home/hosam/Documents/CampusHub-main/packages/Webkul/Core/src/Packages/OptionalPackageComposition.php)
3. [`tests/Feature/Foundation/OptionalPackageCompositionTest.php`](file:///home/hosam/Documents/CampusHub-main/tests/Feature/Foundation/OptionalPackageCompositionTest.php)

---

## 14. Deferred Step 03 Work

The following tasks are explicitly deferred to **Step 03**:
- Updating `composer.json.stub` to support declarative capabilities.
- Updating `provider.php.stub` to remove legacy `class_exists()` probes.
- Updating `AdminGenerator` / `AdminMakeCommand` to inject capability metadata upon generation.
- Modernizing component generator stubs and adding end-to-end tests for V2 generator output.
