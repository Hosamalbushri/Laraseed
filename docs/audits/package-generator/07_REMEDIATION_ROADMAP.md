# Laraseed Package Generator — Phase 01: Remediation Roadmap
## 07. Remediation Roadmap

This roadmap groups all findings identified during the Phase 01 audit into small, independently executable, prioritized remediation steps for subsequent phases.

---

## Remediation Priority Overview

```
+-----------------------------------------------------------------------------------+
|                        PRIORITIZED REMEDIATION ROADMAP                            |
+-----------------------------------------------------------------------------------+
| STEP 1 (HIGH PRIORITY):   Data Integrity & Filesystem Containment                 |
|   - SEC-PG-02: Implement Transactional Rollback in Base PackageGenerator          |
|   - SEC-PG-04: Add Realpath Canonicalization Containment to PackageResolver       |
|                                                                                   |
| STEP 2 (MEDIUM PRIORITY): Content Security Policy (CSP) & Cookie Modernization    |
|   - SEC-PG-01: Refactor Inline Scripts/Styles to Data Attributes & CSS Properties  |
|   - SEC-PG-03: Add SameSite=Lax & Secure Flags to Dark Mode Toggle Cookie         |
|                                                                                   |
| STEP 3 (LOW / OPTIMIZATION): Frontend Runtime & Performance Tuning                |
|   - SEC-PG-06: Optimize Vue 3 Import to Runtime-Only Bundle                       |
|   - SEC-PG-05: Guard Router Lookup Refreshes with routesAreCached() Check         |
|   - SEC-PG-07: Standardize CLI --force Rejection Guidance in WebMakeCommand       |
+-----------------------------------------------------------------------------------+
```

---

## Step-by-Step Remediation Plan

---

### Step 1: Data Integrity & Filesystem Containment (High Priority)

**Target Findings:** `SEC-PG-02`, `SEC-PG-04`

#### 1.1 Add Transactional Rollback to `PackageGenerator.php` (`SEC-PG-02`)
- **Objective:** Ensure that base package scaffolding (`laraseed:make-package`) provides the same rollback safety as `AdminGenerator` and `WebGenerator`.
- **Implementation Details:**
  1. In `PackageGenerator::generate()`, wrap the execution loop in a `try ... catch (\Throwable $e)` block.
  2. Track newly created file paths in an array `$createdPaths`.
  3. Upon exception, delete all newly created files, clean up empty directories in `packages/{Vendor}/{Package}`, and rethrow `PackageGenerationException`.
- **Verification Test:** Add test `test_make_package_mid_flight_failure_performs_transactional_rollback` in `PackageGeneratorTest.php`.

#### 1.2 Add Canonical Path Containment to `PackageResolver.php` (`SEC-PG-04`)
- **Objective:** Prevent package generators from resolving symlinks pointing outside the project `packages/` directory.
- **Implementation Details:**
  1. In `PackageResolver::resolve()`, verify `realpath($packagePath)` starts with `realpath($this->basePath . '/packages')`.
  2. Throw `PackageGenerationException::invalidInput()` if path resolves outside packages root.
- **Verification Test:** Add test `test_package_resolver_rejects_external_symlinks` in `PackageGeneratorTest.php`.

---

### Step 2: Content Security Policy & Cookie Modernization (Medium Priority)

**Target Findings:** `SEC-PG-01`, `SEC-PG-03`

#### 2.1 Refactor Inline Scripts to Unobtrusive Event Handlers (`SEC-PG-01`)
- **Objective:** Allow generated Web packages to run under strict Content Security Policies (`script-src 'self'`).
- **Implementation Details:**
  1. In `header.blade.php.stub`, replace `onclick="..."` on dark mode button with `data-action="toggle-dark-mode"`.
  2. Replace `onclick="..."` on mobile menu button with `data-action="toggle-mobile-menu"`.
  3. In `component_modal.blade.php.stub`, replace inline `onclick` with `data-action="close-modal" data-target="{{ $id }}"`.
  4. In `asset_js.js.stub`, attach global event delegation listeners for `data-action` attributes.
  5. In `layout.blade.php.stub`, inject `--brand-color` as a style attribute on `<html>` or scoped wrapper rather than raw `<style>` block.
- **Verification Test:** Add feature/browser test asserting zero CSP violations under strict CSP policy.

#### 2.2 Add `SameSite=Lax` and `Secure` to Dark Mode Cookie (`SEC-PG-03`)
- **Objective:** Comply with modern cookie security standards.
- **Implementation Details:**
  1. In `asset_js.js.stub`, update cookie string to:  
     `` `dark_mode=${this.isDarkMode ? '1' : '0'}; path=/; max-age=31536000; SameSite=Lax${window.location.protocol === 'https:' ? '; Secure' : ''}` ``.
- **Verification Test:** Unit test asserting cookie header format.

---

### Step 3: Frontend Performance & CLI Ergonomics (Low / Optimization)

**Target Findings:** `SEC-PG-05`, `SEC-PG-06`, `SEC-PG-07`

#### 3.1 Optimize Vue 3 Bundle Size (`SEC-PG-06`)
- **Objective:** Reduce production JavaScript bundle size from ~190 kB to ~50 kB.
- **Implementation Details:**
  1. In `asset_js.js.stub`, change `import { createApp } from "vue/dist/vue.esm-bundler";` to `import { createApp } from "vue";`.
- **Verification Test:** Assert `vite build` produces output JS under 75 kB.

#### 3.2 Optimize Router Lookup Refreshes (`SEC-PG-05`)
- **Objective:** Avoid redundant router lookups during cached routes boot.
- **Implementation Details:**
  1. In `provider.php.stub`, wrap refresh calls in `if (! $this->app->routesAreCached()) { ... }`.
- **Verification Test:** Verify route lookups under route caching.

#### 3.3 Clarify CLI `--force` Semantics in `WebMakeCommand` (`SEC-PG-07`)
- **Objective:** Improve developer feedback when attempting to force overwrite Web capabilities.
- **Implementation Details:**
  1. Update `WebMakeCommand` help description to explain that Web capability scaffolding is strictly non-destructive.
- **Verification Test:** Assert CLI help output.
