# LARASEED — GENERATOR V3
# Implementation Plan: Admin-Based Web Starter Package Generator

- **Date:** 2026-10-02
- **Author:** Principal Laravel Architect & UI Systems Engineer
- **Status:** APPROVED FOR IMPLEMENTATION
- **Deliverable:** `docs/plans/GENERATOR_V3_WEB_STARTER_PLAN.md`

---

## 1. Overview & Architectural Principles

The goal of **Generator V3** is to provide a deterministic, high-safety command for scaffolding a fully functional, Bagisto/Laraseed-standard **Web Package** (or adding a Web capability to an existing package).

### Core Principles:
1. **Zero UI Framework Pollution:** Web packages use the exact styling, Tailwind CSS tokens, Cairo typography, icomoon iconography, and Blade/Vue component conventions already verified in `Webkul\Admin`.
2. **Strict Package Self-Ownership (Rule 09):** The generated Web capability owns 100% of its routes, controllers, views, layout chrome, translations, navigation items, and feature tests inside `packages/Vendor/PackageName/src/Web/`.
3. **Foundation Source Invariance (Rule 08):** Generating, enabling, or physically removing a Web package requires **zero** edits to Foundation packages (`Webkul\Core`, `Webkul\Admin`, `Webkul\User`, `Webkul\DataGrid`, `Webkul\Installer`).
4. **Declarative Capability Composition:** Web packages register via `extra.laraseed.capabilities.web` in `composer.json` and are discovered natively by `OptionalPackageComposition`.
5. **No Mandatory Authentication:** The Web Starter template operates out-of-the-box as a public website with zero authentication dependencies, while providing clean configuration extension points for optional auth guards.

---

## 2. Proposed Generated Web Package Structure

When generating a Web capability for a package (e.g. `Acme/Portal`), the generator creates the following directory structure inside `packages/Acme/Portal/`:

```text
packages/Acme/Portal/
├── composer.json                                       <-- Mutated: adds capabilities.web
├── src/
│   ├── Providers/
│   │   ├── PortalServiceProvider.php                   <-- Base package provider
│   │   └── ModuleServiceProvider.php                   <-- Concord module provider
│   └── Web/
│       ├── Providers/
│       │   └── WebServiceProvider.php                  <-- Web capability composition root
│       ├── Config/
│       │   └── web.php                                 <-- Config: prefix, middleware, auth
│       ├── Http/
│       │   └── Controllers/
│       │       ├── HomeController.php                  <-- Public homepage controller
│       │       └── PageController.php                  <-- Example content page controller
│       ├── Routes/
│       │   └── web.php                                 <-- Package-owned web routes
│       ├── Resources/
│       │   ├── lang/
│       │   │   ├── en/
│       │   │   │   └── app.php                         <-- English static translations
│       │   │   └── ar/
│       │   │       └── app.php                         <-- Arabic static translations (exact parity)
│       │   └── views/
│       │       ├── components/
│       │       │   └── layouts/
│       │       │       ├── index.blade.php             <-- Main Web HTML shell & Vue mount
│       │       │       ├── header/
│       │       │       │   ├── index.blade.php         <-- Top header with logo, dark toggle, lang
│       │       │       │   └── navbar.blade.php        <-- Responsive navigation bar
│       │       │       └── footer/
│       │       │           └── index.blade.php         <-- Web footer component
│       │       ├── home/
│       │       │   └── index.blade.php                 <-- Rich, responsive home page
│       │       └── pages/
│       │           └── show.blade.php                  <-- Example content page
└── tests/
    └── Feature/
        └── Web/
            └── WebPageTest.php                         <-- Feature test asserting web responses
```

---

## 3. Foundation & Admin Reuse Map

### 3.1 Mechanisms Directly Reused
| Feature Area | Reused Mechanism | Source Reference |
|---|---|---|
| **Capability Discovery** | `OptionalPackageComposition::capabilityProviders('web')` | `packages/Webkul/Core/src/Packages/OptionalPackageComposition.php` |
| **Manifest Loading** | `OptionalPackageManifestLoader` | `packages/Webkul/Core/src/Packages/OptionalPackageManifestLoader.php` |
| **Guest Redirection** | `AuthenticationRedirectResolver` | `packages/Webkul/Core/src/Auth/AuthenticationRedirectResolver.php` |
| **Localization & Locale** | App locale, `core()->getCurrentLocale()` | `packages/Webkul/Core/src/Services/ContentLocaleService.php` |
| **Bidirectional Engine** | `in_array(app()->getLocale(), ['fa', 'ar']) ? 'rtl' : 'ltr'` | `packages/Webkul/Admin/src/Resources/views/components/layouts/index.blade.php` |
| **Dark Mode Cookie** | Cookie `dark_mode` (unencrypted in `bootstrap/app.php`) | `bootstrap/app.php:27` |
| **Atomic Generation** | `GenerationPlan`, `FilesystemWriter`, `PackageResolver` | `packages/Laraseed/PackageGenerator/src/Generators/` |

### 3.2 UI Primitives & Styling Reused
- **Typography:** `Cairo` font family and standard system fallbacks.
- **Design Tokens:** `--brand-color` CSS custom property on `:root`.
- **Button Tokens:** `.primary-button`, `.secondary-button`, `.transparent-button`.
- **Form Controls:** Compatible with `<x-admin::form>` and `<x-admin::form.control-group.*>` or package-local Blade form controls.
- **Flash Messages:** Compatible with `<x-admin::flash-group />`.
- **Theme Toggle:** Reusable `<v-dark>` Vue switcher component in header.

---

## 4. CLI Commands & Signatures

### 4.1 Dedicated Web Generator Command
```bash
php artisan laraseed:make-web {package} {--dry-run} {--force}
```
- **Arguments:**
  - `package`: Package name in `Vendor/PackageName` format (e.g. `Acme/Portal`, `Laraseed/Website`).
- **Options:**
  - `--dry-run`: Simulates file generation and `composer.json` capability mutation without touching disk.
  - `--force`: Overwrites existing Web integration files if collisions occur.

### 4.2 Integrated Option in Base Package Generator
```bash
php artisan laraseed:make-package {name} {--web} {--admin} {--dry-run} {--force}
```
Allows creating a new package with Web capability in a single atomic invocation.

---

## 5. Detailed Template Specifications

### 5.1 Main Layout (`src/Web/Resources/views/components/layouts/index.blade.php`)
```blade
<!DOCTYPE html>
<html
    class="{{ request()->cookie('dark_mode') ? 'dark' : '' }}"
    lang="{{ app()->getLocale() }}"
    dir="{{ in_array(app()->getLocale(), ['fa', 'ar']) ? 'rtl' : 'ltr' }}"
>
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>

    @stack('meta')

    <!-- Typography & CSS Tokens -->
    <style>
        :root {
            --brand-color: #0E90D9;
        }
        :root, body {
            font-family: 'Cairo', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif;
        }
    </style>

    @stack('styles')
</head>
<body class="min-h-screen bg-gray-50 text-gray-800 transition-colors duration-200 dark:bg-gray-950 dark:text-gray-100 flex flex-col">
    <div id="app" class="flex flex-col min-h-screen">
        <x-{{ PACKAGE_KEY }}_web::layouts.header />

        <main class="flex-1">
            {{ $slot }}
        </main>

        <x-{{ PACKAGE_KEY }}_web::layouts.footer />
    </div>

    @stack('scripts')
    <script>
        window.addEventListener("load", function() {
            if (window.app && typeof window.app.mount === 'function') {
                window.app.mount("#app");
            }
        });
    </script>
</body>
</html>
```

### 5.2 Responsive Header (`src/Web/Resources/views/components/layouts/header/index.blade.php`)
- **Left/Start:** Dynamic Brand Logo with fallback text title.
- **Center:** Responsive Navigation items (Home, Content page, external/portal links).
- **Right/End:**
  - Dark mode toggle button (`<v-dark>`).
  - Language switcher (English / العربية) with URL parameter or route-based locale switch.
  - Optional profile/login trigger (if auth enabled).

### 5.3 Working Home Page (`src/Web/Resources/views/home/index.blade.php`)
- **Hero Banner:** Compelling translated title, description, and primary call-to-action button.
- **Feature Highlights Grid:** 3-column responsive card grid (adaptive for mobile, tablet, desktop).
- **Call-to-Action Section:** Clean card section with secondary action link.

### 5.4 Example Content Page (`src/Web/Resources/views/pages/show.blade.php`)
- Clean readable content container with breadcrumbs, translated headings, and typographic rhythm.

### 5.5 Localization Parity (`en` & `ar`)
All strings are fully localized with exact key matching:
- English: `packages/Vendor/PackageName/src/Web/Resources/lang/en/app.php`
- Arabic: `packages/Vendor/PackageName/src/Web/Resources/lang/ar/app.php`

---

## 6. Route Ownership & Conflict Prevention Strategy

### 6.1 Route Registration Contract
Routes are registered in `WebServiceProvider.php`:
```php
$prefix = config('{{ PACKAGE_KEY }}_web.prefix', '');

Route::middleware(config('{{ PACKAGE_KEY }}_web.middleware', ['web']))
    ->prefix($prefix)
    ->group(__DIR__ . '/../Routes/web.php');
```

### 6.2 Preventing Conflicts Between Multiple Web Packages
1. **Named Route Isolation:** All routes use the package key prefix:
   - `route('{{ PACKAGE_KEY }}.home')`
   - `route('{{ PACKAGE_KEY }}.page.show')`
2. **Configurable Route Prefix:**
   - Default primary website: `prefix = ''` (binds to `/`).
   - Secondary portal / feature website: `prefix = 'portal'` or `prefix = 'docs'` configured via `.env` or `Config/web.php`.
3. **Zero Collision with Admin / API:** Admin routes reside exclusively under `config('app.admin_path')` (e.g. `/admin`), and API routes under `/api`.

---

## 7. Optional Authentication Strategy

### Supported States:
1. **Public Website (Default):** Zero authentication dependencies. `config('{{ PACKAGE_KEY }}_web.auth.enabled') === false`.
2. **Optional Authentication Integration:**
   - In `Config/web.php`:
     ```php
     'auth' => [
         'enabled' => env('{{ UPPER_PACKAGE_KEY }}_AUTH_ENABLED', false),
         'guard' => 'web',
         'login_route' => 'login',
         'logout_route' => 'logout',
     ],
     ```
   - When enabled, `WebServiceProvider` registers with `AuthenticationRedirectResolver` to handle guest redirects for protected web routes.

---

## 8. Step-by-Step Implementation Roadmap (Generator V3)

```text
Step 1: Stubs & Templates Creation
 ├── web_provider.php.stub
 ├── web_config.php.stub
 ├── web_controller_home.php.stub
 ├── web_controller_page.php.stub
 ├── web_routes_web.php.stub
 ├── web_lang_en.php.stub
 ├── web_lang_ar.php.stub
 ├── web_layout.blade.php.stub
 ├── web_header.blade.php.stub
 ├── web_navbar.blade.php.stub
 ├── web_footer.blade.php.stub
 ├── web_view_home.blade.php.stub
 └── web_view_page.blade.php.stub

Step 2: Web Generator Engine
 └── Laraseed\PackageGenerator\Generators\WebGenerator.php
     ├── Preflight collision check
     ├── Atomic composer.json capability mutation (capabilities.web)
     └── Transactional rollback on mid-flight failure

Step 3: Web Console Command
 ├── Laraseed\PackageGenerator\Console\Commands\WebMakeCommand.php
 └── Registration in PackageGeneratorServiceProvider.php

Step 4: Base Generator Enhancement
 └── Add --web option to Laraseed\PackageGenerator\Console\Commands\PackageMakeCommand.php

Step 5: Test Suite & Matrix Certification
 ├── Unit and Feature tests in tests/Feature/Laraseed/WebPackageGeneratorTest.php
 ├── Validation of dry-run, force, collision, rollback, and manifest loader compatibility
 └── Full test suite execution across all composition states
```

---

## 9. Verification & Quality Gates

Before declaring Generator V3 complete, the following gates must be met:
1. `php artisan test` passes with 100% green status across all suites.
2. Generating a web package creates 100% valid PHP and Blade files with zero syntax errors.
3. Enabling the generated web package boots cleanly with `php artisan route:list` displaying package-owned routes.
4. Foundation packages contain 0 references to generated web packages.
5. Deleting the generated package directory restores the system cleanly without Foundation modifications.

---

- `GENERATOR_V3_PLAN=VERIFIED`
- `READY_FOR_GENERATOR_V3_G02=YES`
