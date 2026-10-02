# Laraseed Generator V3 — Testing, Certification & Release Guide

This document defines the comprehensive testing methodology, verification matrix, automated test structure, browser testing requirements, and production release certification standards for **Laraseed Generator V3**.

---

## 1. Testing Hierarchy & Verification Workflow

To guarantee enterprise-grade stability and prevent regressions, all Web packages and templates must undergo an 8-stage verification pipeline:

```
+-----------------------------------------------------------------------------------+
|                        8-STAGE TESTING & CERTIFICATION PIPELINE                   |
+-----------------------------------------------------------------------------------+
| 1. Unit & Catalog Tests:      WebTemplateCatalog definitions & validation        |
| 2. Generator Scaffolding:     Preflight collision, dry-run & transactional rollback |
| 3. Composition & Activation:  OptionalPackageComposition 4-quadrant checks       |
| 4. Runtime Route Tests:       HTTP 200 responses, dynamic prefixes, root claim   |
| 5. Component & Blade Tests:   Anonymous component rendering & attribute forwarding|
| 6. Translation Parity Tests:  100% key parity between English and Arabic          |
| 7. Production Asset Build:    Vite compilation, bundle creation, manifest check   |
| 8. Browser Visual Audit:      Responsiveness, RTL alignment, dark mode, typography|
+-----------------------------------------------------------------------------------+
```

---

## 2. Automated Tests vs. Browser Visual Environment

| Test Type | Execution Environment | What it Verifies | Tools / Runner |
| :--- | :--- | :--- | :--- |
| **Headless Feature Tests** | PHP CLI (Automated) | HTTP status codes, routing, middleware enforcement, auth redirects, translation keys, JSON responses. | PHPUnit / Pest (`./vendor/bin/pest`) |
| **Asset Compilation Tests** | Node.js CLI (Automated) | Vite build exit code, CSS/JS bundling, `manifest.json` generation, file size bounds. | `npx vite build` |
| **Browser Visual Tests** | Headless / Real Browser | Responsive breakpoints (mobile 390px, tablet 768px, desktop 1440px), Cairo font rendering, RTL alignment, CSS layout, JavaScript interaction. | Headless Chrome, Playwright, Dusk |

---

## 3. The 10 Essential Automated Test Cases

Every template and generated Web package must implement or satisfy the following test cases in `tests/Feature/Web/`:

### 1. Template Catalog Registration Test
Verifies that the template is registered in `WebTemplateCatalog` and provides complete file mappings:
```php
public function test_template_is_registered_in_catalog(): void
{
    $this->assertTrue(WebTemplateCatalog::has('starter'));
    $template = WebTemplateCatalog::get('starter');
    $this->assertArrayHasKey('package.json', $template['files']);
}
```

### 2. Scaffolding & Preflight Collision Test
Verifies clean file creation on a fresh package and collision prevention on subsequent runs:
```php
public function test_scaffolding_and_collision_preflight(): void
{
    $this->artisan('laraseed:make-package Test/TestPkg')->assertExitCode(0);
    $this->artisan('laraseed:make-web Test/TestPkg')->assertExitCode(0);
    // Re-running without force must fail cleanly
    $this->artisan('laraseed:make-web Test/TestPkg')->assertExitCode(1);
}
```

### 3. Dry-Run Zero Mutation Test
Verifies that `--dry-run` creates 0 files and leaves `composer.json` untouched:
```php
public function test_dry_run_creates_zero_files(): void
{
    $this->artisan('laraseed:make-web Test/DryRunPkg --dry-run')->assertExitCode(0);
    $this->assertFalse(file_exists(base_path('packages/Test/DryRunPkg/src/Web')));
}
```

### 4. Transactional Rollback Test
Verifies that mid-flight write errors cleanly delete partial files and restore original manifests.

### 5. Composition 4-Quadrant Lifecycle Test
Verifies that routes and providers only boot when both the package and capability are active.

### 6. Public Route HTTP Status Test
Verifies that public routes return HTTP 200 and render expected view strings:
```php
public function test_web_routes_render_successfully(): void
{
    $this->get(route('test_pkg.web.home'))
        ->assertStatus(200)
        ->assertSee('Test Pkg');
}
```

### 7. Translation Key Parity Test
Verifies 100% key parity between English and Arabic:
```php
public function test_translation_parity(): void
{
    $en = require __DIR__ . '/../../../src/Web/Resources/lang/en/app.php';
    $ar = require __DIR__ . '/../../../src/Web/Resources/lang/ar/app.php';

    $this->assertSame(array_keys($en['web']), array_keys($ar['web']));
}
```

### 8. Production Asset Build & Manifest Test
Verifies that running `vite build` creates `public/{package_slug}/web/build/manifest.json`.

### 9. Route & Auth Isolation Test
Verifies that unauthenticated requests to protected Web routes redirect to package login without capturing Admin requests.

### 10. Multi-Package Coexistence Test
Verifies that multiple Web packages can run concurrently with distinct URL prefixes and asset builds.

---

## 4. Running the Test Suite

### Run All Package Generator & Web Tests:
```bash
./vendor/bin/pest tests/Feature/Laraseed/WebPackageGeneratorTest.php
```

### Run Full Regression Test Suite:
```bash
./vendor/bin/pest
```

---

## 5. Production Release Checklist

Before releasing a new package or deploying a Web capability to production, verify:

- [ ] **PSR-4 Autoloading:** Namespace declared in `composer.json` and dumped with `composer dump-autoload --optimize`.
- [ ] **Production Asset Build:** `npm run build` executed and verified in `public/{package-slug}/web/build/`.
- [ ] **Package Activation:** Package ID added to `LARASEED_OPTIONAL_PACKAGES` in production `.env`.
- [ ] **Route Caching:** Tested under `php artisan route:cache`.
- [ ] **Config Caching:** Tested under `php artisan config:cache`.
- [ ] **Translation Parity:** Verified 100% parity across English and Arabic language files.
- [ ] **Visual Audit:** Responsive layout, RTL rendering, and dark mode verified in a browser.
- [ ] **Security:** Auth guard isolated, POST logout configured with CSRF token.
