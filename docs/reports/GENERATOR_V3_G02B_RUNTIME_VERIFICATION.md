# LARASEED GENERATOR V3 — G02-B REPORT
## Web Starter Runtime, Assets & Browser Verification

**Document Status:** Complete & Verified  
**Date:** 2026-10-02  
**Command:** `php artisan laraseed:make-web Vendor/PackageName`  
**Test Suite Status:** 359 Passed, 2866 Assertions (100% Green, 0 Regressions)

---

## 1. Actual Defects Discovered & Resolved

During the G02-B runtime and asset pipeline verification, three specific defects were identified and resolved:

1. **Missing Standalone Asset Pipeline Stubs & Configs:**
   - *Defect:* The initial G02 implementation scaffolded `app.css` and `app.js` but omitted package-level build manifests (`package.json`, `vite.config.js`, `tailwind.config.js`, `postcss.config.js`). Running standalone Vite compilation was blocked without manual developer boilerplate.
   - *Fix:* Added stubs for `package.json`, `vite.config.js`, `tailwind.config.js`, and `postcss.config.js` into `Laraseed/PackageGenerator` and included them in the atomic generation plan.

2. **Tailwind `@apply` Syntax Error with Arbitrary CSS Variables:**
   - *Defect:* PostCSS failed during Vite production build with `The 'hover:bg-[var(--brand-color)]/10' class does not exist` when compiling `@layer components`.
   - *Fix:* Aligned CSS button component classes with verified `Webkul/Admin` Tailwind utilities using `bg-brandColor` (mapped to `var(--brand-color)` in `tailwind.config.js`) and standard hex opacity overlays.

3. **Dynamic Hot / Production Asset Injection & Fallback in Blade Layout:**
   - *Defect:* Unconditional calls to `vite()->set(...)` without checking for the build manifest or running Vite dev server would trigger `ViteManifestNotFoundException` prior to running `npm run build`.
   - *Fix:* Updated `web_provider.php.stub` to dynamically register the package viter (`krayin-vite.viters.{package_key}_web`), and updated `web_layout.blade.php.stub` with conditional asset injection (`@if (file_exists(public_path('{package_key}-web-vite.hot')) || file_exists(public_path('{package_slug}/web/build/manifest.json')))`), maintaining clean fallback to baseline styles prior to compilation.

4. **Silent Route Overwrite on Multiple Root Route (`/`) Claims:**
   - *Defect:* Two enabled Web packages setting `prefix => ''` would silently overwrite each other's `/` route in Laravel's RouteCollection.
   - *Fix:* Implemented explicit collision detection in `WebServiceProvider::boot()`. When prefix is empty, it verifies and claims `config('laraseed.web.root_owner')`, throwing a clear `\RuntimeException` if another package already claimed `/`.

---

## 2. Exact Files Modified & Added

### Stubs & Generators in `packages/Laraseed/PackageGenerator/`
- `stubs/web_package_json.json.stub` *(New)* — Standalone Node / npm build dependencies.
- `stubs/web_vite_config.js.stub` *(New)* — Package Vite configuration with public output and hot file paths.
- `stubs/web_tailwind_config.js.stub` *(New)* — Tailwind content scanner and brandColor configuration.
- `stubs/web_postcss_config.js.stub` *(New)* — PostCSS configuration with Tailwind and Autoprefixer.
- `stubs/web_provider.php.stub` *(Updated)* — Dynamic viter registration and root route conflict diagnostic.
- `stubs/web_layout.blade.php.stub` *(Updated)* — Conditional Vite asset loading and responsive baseline styling.
- `stubs/web_header.blade.php.stub` *(Updated)* — Responsive mobile hamburger toggle, mobile menu drawer, and locale switch link.
- `stubs/web_controller_home.php.stub` *(Updated)* — Locale request query parameter handling.
- `stubs/web_controller_page.php.stub` *(Updated)* — Locale request query parameter handling.
- `stubs/web_asset_css.css.stub` *(Updated)* — Verified Tailwind layer utilities with brandColor.
- `stubs/web_asset_js.js.stub` *(Updated)* — Vue 3 initialization with automated mounting on `#app`.
- `src/Generators/WebGenerator.php` *(Updated)* — Added asset pipeline files to atomic generation plan and preflight checks.

### Test Suite
- `tests/Feature/Laraseed/WebPackageGeneratorTest.php` *(Updated)* — Added 5 comprehensive tests (Vite production build verification, dual Web package prefix isolation, root route conflict diagnostic, responsive mobile drawer rendering, and RTL/LTR locale switching).

---

## 3. Asset Build Commands & Results

### Build Command
From inside any generated Web package:
```bash
npm run build
# OR
npx vite build
```

### Build Output Verification
When executing `npx vite build` in generated package `packages/AcmeTest/WebAssetPkg`:
```text
vite v5.4.12 building for production...
transforming...
✓ 3 modules transformed.
rendering chunks...
computing gzip size...
../../../public/acme-test-web-asset-pkg/web/build/manifest.json              0.41 kB │ gzip: 0.17 kB
../../../public/acme-test-web-asset-pkg/web/build/assets/app-CoE2DqX9.css     4.89 kB │ gzip: 1.48 kB
../../../public/acme-test-web-asset-pkg/web/build/assets/app-ByF42kIq.js    158.33 kB │ gzip: 53.64 kB
✓ built in 540ms
```

### Asset Loading Verification
1. `public/{package_slug}/web/build/manifest.json` is generated with valid hashes.
2. Blade template evaluates `vite()->set(['src/Web/Resources/assets/css/app.css', 'src/Web/Resources/assets/js/app.js'], '{package_key}_web')`.
3. Rendered HTML successfully outputs `<link rel="stylesheet" href=".../assets/app-CoE2DqX9.css">` and `<script type="module" src=".../assets/app-ByF42kIq.js"></script>`.

---

## 4. Runtime Route Inventory

Each generated Web package registers the following isolated routes:

| Route Name | Default URI | HTTP Method | Action |
| :--- | :--- | :--- | :--- |
| `{package_key}.web.home` | `/{package_slug}` | `GET` | `HomeController@index` |
| `{package_key}.web.pages.show` | `/{package_slug}/pages/{page?}` | `GET` | `PageController@show` |

### Route Prefix Customization & Root Mount
- Default prefix is `{vendor-kebab}-{package-kebab}`.
- To claim root `/`, developers set `'prefix' => ''` in `config/{package_key}_web.php`.
- If two packages attempt to set `'prefix' => ''`, the second throws:
  `Route conflict: Package [pkg_b] attempted to claim the root route [/], but it is already owned by [pkg_a].`

---

## 5. Browser Test Evidence & Status

- **Automated Browser (Playwright / Puppeteer):** **BLOCKED**. No headless browser binary is installed in the CLI container for running graphical screenshot and visual viewport automation.
- **HTTP / DOM Structural Verification:** **VERIFIED**. Verified through PHPUnit DOM assertions:
  - Header with branding, navigation links, dark mode toggle button, locale switcher link.
  - Mobile hamburger toggle button with click handler `document.getElementById('mobile-menu').classList.toggle('hidden')`.
  - Mobile dropdown drawer (`id="mobile-menu"`) containing mobile nav links.
  - Cairo font family declarations in `:root` and `@font-face`.
  - Dark mode support (`cookie: dark_mode=1` and class `.dark`).

---

## 6. Localization & Responsive Verification

- **English (`en`):** HTML tag renders `lang="en" dir="ltr"`. All translation keys resolve to English.
- **Arabic (`ar`):** HTML tag renders `lang="ar" dir="rtl"`. All translation keys resolve to Arabic with 100% key parity with English.
- **Dynamic Switcher:** Requesting `?locale=ar` or `?locale=en` switches locale in session and updates layout directionality immediately.
- **Responsive Breakpoints:**
  - Desktop (`md:` and above): Header renders horizontal navigation bar; mobile menu is hidden.
  - Mobile (`< md`): Navigation bar collapses; hamburger button toggles responsive drawer.

---

## 7. Package Isolation Results

- **Multiple Web Packages:** Tested coexistence of `AcmeTest/WebBlogPkg` (prefix `/acme-test-web-blog-pkg`) and `AcmeTest/WebShopPkg` (prefix `/acme-test-web-shop-pkg`). Both serve independent routes and views with zero interference.
- **Admin + Web Dual Capability:** Tested package with both `make-admin` and `make-web`. Both capabilities coexist in `composer.json` and boot their respective providers.
- **Removability:** Removing the package directory leaves zero residual entries in `bootstrap/providers.php` or `config/laraseed.php`.
- **Zero Framework Mutations:** `packages/Webkul/*` and `packages/Laraseed/Contacts` remain 100% untouched.

---

## 8. Full Regression Test Results

Running `php artisan test`:
```text
Tests:    359 passed (2866 assertions)
Duration: 14.66s
```
- `WebPackageGeneratorTest`: 17 passed, 126 assertions.
- `PackageGeneratorTest` (V2 Suite): 66 passed, 312 assertions.
- Foundation & Contacts Suite: 276 passed, 2428 assertions.

---

## 9. Remaining Limitations

1. **Headless Browser Execution:** Visual layout testing (pixel-by-pixel rendering across physical viewports) is not automated via Playwright due to missing browser binaries in the environment.
2. **Global Vite Monorepo Bundler:** In G02-B, each package compiles its own assets via its standalone `vite.config.js`. Future enhancement could provide an optional root Vite alias configuration to compile all package assets in a single command.

---

## 10. Status Indicators

```ini
GENERATOR_V3_G02B=PASS
WEB_PRODUCTION_BUILD=VERIFIED
WEB_BROWSER_RENDERING=BLOCKED
WEB_ROUTE_ISOLATION=VERIFIED
GENERATOR_V2_REGRESSION=PASS
READY_FOR_GENERATOR_V3_G03=YES
```
