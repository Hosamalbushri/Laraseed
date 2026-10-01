# Laraseed — Step 06-C: Universal Package Discovery & Runtime Composition Hardening

**Date:** 2026-10-01  
**Author:** Antigravity Pair Programming (Principal Architecture Audit)  
**Status:** Certified & Passing (`LARASEED_STEP_06C=PASS`)

---

## 1. Initial Runtime Baseline

Before conducting the hardening audit, the baseline runtime state was validated:
- **Test Suite:** 316 passing tests, 2,497 assertions.
- **Foundation-Only Routes:** Exactly 67 routes (`LARASEED_OPTIONAL_PACKAGES=""`).
- **Contacts + Admin Routes:** Exactly 79 routes (`LARASEED_OPTIONAL_PACKAGES="contacts"`).
- **Composer Status:** Strict validation passes on root `composer.json` and package `composer.json`.

---

## 2. Composer Autoload Findings

### 2.1 Context & Discovery
In Step 06-B, adding `"Laraseed\\Contacts\\": "packages/Laraseed/Contacts/src"` in the root [`composer.json`](file:///home/hosam/Documents/CampusHub-main/composer.json) was required for production and CLI runtimes.

### 2.2 Mechanism Evaluation
- **Root PSR-4 Mapping:** Maps namespace prefix directly to package `src/` directory. Identical to Foundation packages (`Webkul\Admin\`, `Webkul\Core\`, `Webkul\DataGrid\`, etc.).
- **Composer Path Repositories (`packages/*/*`):** Root `composer.json` contains a path repository pattern. Path repositories require explicit `composer require vendor/package:@dev` to create symlinks under `vendor/` and read package-level `composer.json` autoload sections.
- **Wildcard / Glob Autoloading:** Composer PSR-4 specification strictly prohibits wildcards in PSR-4 paths. Custom runtime `spl_autoload_register` hacks are strongly rejected as anti-patterns in production.

### 2.3 Selected Strategy
Standard Monorepo Root PSR-4 Mapping with explicit namespace registration in root `composer.json`, coupled with `composer dump-autoload`. This ensures identical mechanics between Foundation packages and Laraseed Optional packages, deterministic static analysis, zero filesystem symlink issues, and strict Composer validation compliance.

---

## 3. Manifest Discovery Findings

In [`config/laraseed.php`](file:///home/hosam/Documents/CampusHub-main/config/laraseed.php), optional packages are discovered dynamically across the monorepo:
```php
$manifestPaths = array_values(array_filter(
    glob(dirname(__DIR__) . '/packages/*/*/composer.json') ?: [],
    function (string $path): bool {
        if (! is_file($path)) {
            return false;
        }

        $data = json_decode((string) file_get_contents($path), true);

        return is_array($data) && ($data['extra']['laraseed']['type'] ?? null) === 'optional';
    }
));

$catalog = (new OptionalPackageManifestLoader)->load($manifestPaths);
```

### 3.1 Package Classification Accuracy
1. **Foundation Packages (`packages/Webkul/*`):** Do not declare `extra.laraseed.type: optional`. Automatically excluded from the optional catalog.
2. **Development Tooling (`packages/Laraseed/PackageGenerator`):** Declares `extra.laravel.providers`, no `extra.laraseed.type`. Excluded.
3. **Optional Business Packages (`packages/Laraseed/Contacts`):** Declares `extra.laraseed.type: optional`. Successfully discovered and ingested into `$catalog`.
4. **Installed vs Enabled Isolation:** Discovered packages are indexed in `$catalog` as `INSTALLED`. They remain `DISABLED` until explicitly added to the `LARASEED_OPTIONAL_PACKAGES` environment variable.

---

## 4. Configuration Initialization Analysis

The runtime lifecycle transitions seamlessly:
1. **Bootstrap Initialization:** Laravel starts, loading `.env` configuration.
2. **Config Compilation (`LoadConfiguration`):**
   - In uncached environments, `config/laraseed.php` evaluates `$catalog` from discovered manifests and filters by `env('LARASEED_OPTIONAL_PACKAGES')`.
   - In cached environments (`php artisan config:cache`), pre-compiled configuration arrays are loaded directly from `bootstrap/cache/config.php` in a single opcode step.
3. **Container Service Providers:** `bootstrap/providers.php` reads `config('laraseed.optional_packages.providers')` and mounts active providers.

---

## 5. Concord Initialization Analysis

### 5.1 Root Cause of Config Ordering Nuance
Laravel boots configuration files in alphabetical order. Consequently, `config/concord.php` evaluates before `config/laraseed.php`. Calling `config('laraseed.optional_packages.concord_modules')` during `concord.php` evaluation returned `null` prior to Step 06-B.

### 5.2 Resolution Audit
In [`config/concord.php`](file:///home/hosam/Documents/CampusHub-main/config/concord.php):
```php
$optionalModules = (require __DIR__.'/laraseed.php')['optional_packages']['concord_modules'] ?? [];
```
- During uncached development, requiring `laraseed.php` ensures deterministic Concord module availability without circular dependency.
- During production configuration caching (`php artisan config:cache`), Laravel compiles the merged array directly into `config.php`, eliminating redundant runtime evaluations.

---

## 6. Root Cause Summary of Architectural Defects Addressed

| Issue | Root Cause | Resolution |
|---|---|---|
| Autoloading in CLI/Runtime | Newly created package not in root PSR-4 map | Mapped `"Laraseed\\Contacts\\": "packages/Laraseed/Contacts/src"` in root `composer.json` + `dump-autoload` |
| Static Catalog Discovery | `config/laraseed.php` had hardcoded `load([])` | Replaced with dynamic discovery across `packages/*/*/composer.json` matching `type: optional` |
| Concord Timing Mismatch | Alphabetical config parsing (`concord.php` before `laraseed.php`) | Required `laraseed.php` directly in `config/concord.php` for uncached state |
| Test Suite Environment Bleed | Testing environment read `.env` with `contacts` enabled | Added `<env name="LARASEED_OPTIONAL_PACKAGES" value="" />` to `phpunit.xml` |

---

## 7. Selected Universal Package Installation Strategy

**Standard Monorepo Protocol:**
1. **Scaffold:** Generate package with `php artisan laraseed:make-package Vendor/PackageName`.
2. **Autoload Register:** Add `"Vendor\\PackageName\\": "packages/Vendor/PackageName/src"` to root `composer.json`'s `autoload.psr-4`.
3. **Dump Autoload:** Execute `composer dump-autoload`.
4. **Enable:** Add package ID to `LARASEED_OPTIONAL_PACKAGES` in `.env`.

---

## 8. Rejected Alternatives & Architectural Reasons

1. **Custom Production SPL Autoloader:**
   - *Rejected:* Bypasses Composer optimization, breaks static analysis (PHPStan/Psalm/IDE), causes hidden class resolution bugs.
2. **Full Composer Path Repositories with `composer require` on Every Scaffold:**
   - *Rejected:* Unnecessarily creates symlinks in `vendor/`, requires network/dev-stability flags, and contradicts the established Foundation monorepo structure.
3. **Runtime `class_exists()` Feature Probing:**
   - *Rejected:* Violates declarative capability architecture and creates invisible coupling.

---

## 9. Exact Files Inspected and Modified

- [`composer.json`](file:///home/hosam/Documents/CampusHub-main/composer.json) (Root PSR-4 mapping)
- [`config/laraseed.php`](file:///home/hosam/Documents/CampusHub-main/config/laraseed.php) (Dynamic manifest discovery)
- [`config/concord.php`](file:///home/hosam/Documents/CampusHub-main/config/concord.php) (Early module resolution)
- [`phpunit.xml`](file:///home/hosam/Documents/CampusHub-main/phpunit.xml) (Default testing environment isolation)
- [`tests/Composition/UniversalPackageLifecycleTest.php`](file:///home/hosam/Documents/CampusHub-main/tests/Composition/UniversalPackageLifecycleTest.php) (Authored comprehensive lifecycle & quad-state test suite)

---

## 10. Generator Compatibility

The frozen **Package Generator V2** remains 100% compliant:
- Generated manifests produce valid `extra.laraseed` metadata.
- Generated provider structures match `OptionalPackageManifestLoader` expectations.
- Declarative capabilities generated via `laraseed:make-admin` conform to `capabilities.admin` specs.

---

## 11. Second-Package Experiment

Executed automated quad-state testing in [`UniversalPackageLifecycleTest.php`](file:///home/hosam/Documents/CampusHub-main/tests/Composition/UniversalPackageLifecycleTest.php):
1. **Foundation-Only:** Fixtures dormant, 0 providers, 0 capabilities.
2. **Single Package Enabled (`contacts`):** Contacts active, secondary fixture dormant.
3. **Multi-Package Composition (`contacts` + `second_fixture`):** Dependency ordering maintained, reverse dependent rules enforced (`canDisable('contacts') === false`).
4. **Invalid Dependency Ingestion:** Attempting to enable `second_fixture` without required `contacts` immediately throws `InvalidPackageComposition`.

---

## 12. Configuration Cache Verification

Verified via CLI:
```bash
php artisan config:cache
php artisan route:list # -> Exactly 79 routes
php artisan laraseed:packages # -> Contacts ACTIVE, admin:ON
php artisan config:clear
```
All capabilities and route compositions remain identical in cached and uncached modes.

---

## 13. Capability Composition Verification

Verified `AdminServiceProvider::registerCapabilityProviders()` correctly discovers and activates capability providers declared by enabled optional packages without runtime probing or hardcoded references.

---

## 14. Contacts Runtime Regression

- **Domain Model:** Validated with Concord proxy resolution.
- **Repository Operations:** Validated with transactions and event dispatching.
- **Headless API:** 17 tests passed (all CRUD endpoints, validation, pagination).
- **Admin Capability:** 20 tests passed (CRUD, localization key parity, ACL bouncer visibility).

---

## 15. Admin Menu & ACL Verification

- **Authorized User:** `menu()->getItems('admin')` resolves `contacts` with route `admin.contacts.index` and localized title.
- **Unauthorized User:** `menu()->getItems('admin')` excludes `contacts` via Webkul Core `Bouncer`.
- **Super Admin:** Full visibility and access maintained.

---

## 16. Full Test Suite Results

```text
Tests:    320 passed (2544 assertions)
Duration: 9.81s
```
- Root & Composition Suite: 222 tests
- Contacts Package Suite: 98 tests

---

## 17. Remaining Risks

- **Zero High or Medium Risks Identified.**
- **Operational Hygiene:** When adding a new optional package, developers must remember to add the PSR-4 entry in root `composer.json` and execute `composer dump-autoload`.

---

## 18. Final Readiness Decision

```text
LARASEED_STEP_06C=PASS

PACKAGE_AUTOLOAD=VERIFIED
PACKAGE_DISCOVERY=VERIFIED
COMPOSITION_SINGLE_SOURCE_OF_TRUTH=VERIFIED

CONCORD_INITIALIZATION=VERIFIED
CONFIG_CACHE_COMPATIBILITY=VERIFIED

SECOND_PACKAGE_EXPERIMENT=PASS
CAPABILITY_ISOLATION=PASS

CONTACTS_DOMAIN_REGRESSION=PASS
CONTACTS_API_REGRESSION=PASS
CONTACTS_ADMIN_REGRESSION=PASS

FOUNDATION_ONLY_RUNTIME=PASS
CONTACTS_ENABLED_RUNTIME=PASS

READY_FOR_CONTACTS_STEP_07=YES
```
