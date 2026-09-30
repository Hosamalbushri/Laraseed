# CAMPUSHUB — OPTIONAL PACKAGES RUNTIME ACTIVATION FORENSIC AUDIT REPORT

## 1. Executive Summary

A forensic runtime audit was conducted to diagnose why the optional packages (`Student`, `LostAndFound`, and `Website`) physically existed in the repository and passed all Pest test suites, yet appeared disabled when opening the live application in the browser (`http://127.0.0.1:8000`).

The root cause was proven at the first stage of the runtime configuration pipeline:
- The local `.env` file did **not** define `CAMPUSHUB_OPTIONAL_PACKAGES`.
- `config/campushub.php` reads `env('CAMPUSHUB_OPTIONAL_PACKAGES', '')`, which defaulted to `''` (Foundation-only mode) whenever no CLI environment variable override was passed.
- Previous test runs explicitly supplied `CAMPUSHUB_OPTIONAL_PACKAGES=...` in the command environment, masking the fact that the local `.env` used by the live web server (`php artisan serve` on `127.0.0.1:8000`) and unprefixed CLI commands resolved `enabled = []`.

Setting `CAMPUSHUB_OPTIONAL_PACKAGES=student,lost_and_found,website` in `.env` immediately activated all three optional packages across both the CLI and the live web server without modifying any architectural or composition code.

---

## 2. Symptom Reproduced

Before remediation, querying the unprefixed CLI runtime and the live HTTP server (`http://127.0.0.1:8000/`) reproduced the exact symptom:
- `GET http://127.0.0.1:8000/` returned `<div class="web-home__empty">` (the Foundation `Webkul\Web` fallback empty state) with zero Website sections.
- Unprefixed CLI inspection (`php artisan route:list`) registered only **69 Foundation routes**, `HOME_SECTIONS=[]`, `HEADER_NAV=[]`, and `FOOTER_NAV=[]`.

---

## 3. Installed Packages

All three optional packages are physically installed in `packages/Webkul/`:
- `Student`: `packages/Webkul/Student` (`INSTALLED = YES`)
- `LostAndFound`: `packages/Webkul/LostAndFound` (`INSTALLED = YES`)
- `Website`: `packages/Webkul/Website` (`INSTALLED = YES`)

---

## 4. Raw Environment State

### Before Remediation
- `.env`: Did not contain `CAMPUSHUB_OPTIONAL_PACKAGES` (`getenv('CAMPUSHUB_OPTIONAL_PACKAGES') === false`, `env('CAMPUSHUB_OPTIONAL_PACKAGES') === null`).
- `.env.example`: Contained `CAMPUSHUB_OPTIONAL_PACKAGES=` (line 11) as the repository template default.

### After Remediation
- `.env`: Contains `CAMPUSHUB_OPTIONAL_PACKAGES=student,lost_and_found,website` (`RAW_ENV_VALUE="student,lost_and_found,website"`).
- `.env.example`: Preserves `CAMPUSHUB_OPTIONAL_PACKAGES=` as the template default.

---

## 5. Config State

File: `config/campushub.php`
```php
$enabled = OptionalPackageComposition::parseEnabledPackageIds(
    (string) env('CAMPUSHUB_OPTIONAL_PACKAGES', ''),
);
```
- **Before Remediation**:
  - `config('campushub.optional_packages.enabled')` = `[]`
  - `config('campushub.optional_packages.providers')` = `[]`
- **After Remediation**:
  - `config('campushub.optional_packages.enabled')` = `["student", "lost_and_found", "website"]`
  - `config('campushub.optional_packages.providers')` = `["Webkul\\Student\\Providers\\StudentServiceProvider", "Webkul\\LostAndFound\\Providers\\LostAndFoundServiceProvider", "Webkul\\Website\\Providers\\WebsiteServiceProvider"]`

---

## 6. Config Cache State

Inspected `bootstrap/cache/` and `app()->configurationIsCached()`:
- `bootstrap/cache/config.php`: **Absent** (`CONFIG_CACHED = NO`)
- `bootstrap/cache/routes-*.php`: **Absent** (`ROUTE_CACHED = NO`)
- Configuration caching was not the cause; `.env` was being read live on every request.

---

## 7. Manifest Catalog

`config/campushub.php` loads the explicit manifest catalog via `OptionalPackageManifestLoader`:
1. `base_path('packages/Webkul/Student/composer.json')` — exists and readable
2. `base_path('packages/Webkul/LostAndFound/composer.json')` — exists and readable
3. `base_path('packages/Webkul/Website/composer.json')` — exists and readable

Catalog keys loaded: `["lost_and_found", "student", "website"]`.

---

## 8. Package Manifest Metadata

Verified from each package's `composer.json`:

| Package | `extra.campushub.id` | `extra.campushub.type` | `extra.campushub.provider` | `extra.campushub.concord_module` |Effective `requires` |
|---|---|---|---|---|---|
| `Student` | `student` | `optional` | `Webkul\Student\Providers\StudentServiceProvider` | `null` | `[]` |
| `LostAndFound` | `lost_and_found` | `optional` | `Webkul\LostAndFound\Providers\LostAndFoundServiceProvider` | `Webkul\LostAndFound\Providers\ModuleServiceProvider` | `["student"]` (derived from `require.webkul/student`) |
| `Website` | `website` | `optional` | `Webkul\Website\Providers\WebsiteServiceProvider` | `null` | `[]` |

---

## 9. Requested Composition

With `.env` configured for full local development:
```text
REQUESTED:
student,lost_and_found,website
```

---

## 10. Resolved Composition

`OptionalPackageComposition::enabledPackages()` resolves:
```text
RESOLVED:
student
lost_and_found
website
```

---

## 11. Dependency Ordering

`OptionalPackageComposition::dependencyGraph()` resolves:
```json
{
    "lost_and_found": ["student"],
    "student": [],
    "website": []
}
```
Topological sorting guarantees `student` is always registered and booted before `lost_and_found`, followed by `website`.

---

## 12. Provider Registration

Verified via `$app->getProvider(...)` in the live runtime:
- **Student provider**:
  - `manifest provider` = `Webkul\Student\Providers\StudentServiceProvider`
  - `registered` = `YES`
- **LostAndFound provider**:
  - `manifest provider` = `Webkul\LostAndFound\Providers\LostAndFoundServiceProvider`
  - `registered` = `YES`
- **Website provider**:
  - `manifest provider` = `Webkul\Website\Providers\WebsiteServiceProvider`
  - `registered` = `YES`
- **Website LostAndFound Integration provider**:
  - `provider` = `Webkul\Website\Integrations\LostAndFound\WebsiteLostAndFoundServiceProvider`
  - `registered` = `YES`

---

## 13. Provider Boot State

Verified via `$app->isBooted()` and runtime service/registry inspection after kernel bootstrap:
- `StudentServiceProvider`: **BOOTED = YES** (Student guard, ACL, menu, translations, views, and routes active)
- `LostAndFoundServiceProvider`: **BOOTED = YES** (`PublicLostAndFoundReadContract` singleton bound, employee/student routes, ACL, and views active)
- `WebsiteServiceProvider`: **BOOTED = YES** (Website sections, navigation, translations, views, `/about` route, and conditional `WebsiteLostAndFoundServiceProvider` booted)

---

## 14. Route Registration

Classified from `php artisan route:list` in the active runtime:
- **Foundation routes** (`Admin`, `User`, `Installer`, `Web`): **69**
  - Representative: `web.home` (`GET /`), `web.locale.switch` (`GET|POST web/locale/{code}`), `admin.dashboard.index` (`GET admin/dashboard`)
- **Student routes**: **12**
  - Representative: `student.session.create` (`GET student/login`), `admin.students.index` (`GET admin/students`)
- **LostAndFound routes**: **21**
  - Representative: `admin.lost_found.items.index` (`GET admin/lost-found/items`), `student.lost_found.claims.store` (`POST student/lost-found/items/{itemId}/claims`)
- **Website routes** (including LostAndFound presentation integration): **3**
  - Representative: `website.about` (`GET about`), `website.lost_found.index` (`GET lost-found`), `website.lost_found.show` (`GET lost-found/{reference}`)
- **Total routes**: **105**

---

## 15. Website Section Registry

Inspected `app(SectionRegistryContract::class)->getSections('home')` at runtime:
1. `website_hero` (`order: 10`, `view: website::sections.hero`)
2. `website_features` (`order: 20`, `view: website::sections.features`)
3. `website_lost_found` (`order: 25`, `view: website::sections.lost-found`)
4. `website_announcements` (`order: 30`, `view: website::sections.announcements`)

---

## 16. Website Navigation Registry

Inspected `app(NavigationRegistryContract::class)` at runtime:
- **Header (`location: header`)**:
  1. `website_home` (`order: 10`, `url: /`)
  2. `website_lost_found` (`order: 15`, `url: /lost-found`)
  3. `website_about` (`order: 20`, `url: /about`)
- **Footer (`location: footer`)**:
  1. `website_footer_about` (`order: 10`, `url: /about`)
  2. `website_footer_lost_found` (`order: 15`, `url: /lost-found`)

---

## 17. LostAndFound Website Integration

Traced execution chain:
```text
WebsiteServiceProvider::boot()
  ↓
WebsiteServiceProvider::registerIntegrations()
  ↓
app(OptionalPackageComposition::class)->enabledPackages() contains 'lost_and_found' (TRUE)
  ↓
$app->register(WebsiteLostAndFoundServiceProvider::class)
  ↓
WebsiteLostAndFoundServiceProvider::boot()
  ├── loads routes (website.lost_found.index, website.lost_found.show) + refreshes router lookups
  ├── registers 'website_lost_found' section (order 25) on 'home'
  └── registers 'website_lost_found' (header) and 'website_footer_lost_found' (footer) navigation items
```

---

## 18. Public Read Contract Binding

Verified in the active container:
```text
CONTRACT_BOUND=YES
IMPLEMENTATION=Webkul\LostAndFound\Services\PublicLostAndFoundService
RESOLUTION_SUCCESS=YES
```

---

## 19. Composer Autoload State

Inspected `composer.json` and `vendor/composer/autoload_psr4.php`:
- `Webkul\Student\` → `packages/Webkul/Student/src`
- `Webkul\LostAndFound\` → `packages/Webkul/LostAndFound/src`
- `Webkul\Website\` → `packages/Webkul/Website/src`
Composer autoload metadata was completely up-to-date (`AUTOLOAD_STALE = NO`).

---

## 20. Laravel Bootstrap Cache State

Inspected `bootstrap/cache/services.php`:
- Prior to `.env` remediation, `bootstrap/cache/services.php` was recompiled by unprefixed requests without optional providers because `CAMPUSHUB_OPTIONAL_PACKAGES` was unset in `.env`.
- After setting `CAMPUSHUB_OPTIONAL_PACKAGES=student,lost_and_found,website` in `.env`, `ProviderRepository` automatically recompiled `bootstrap/cache/services.php` to include `StudentServiceProvider`, `LostAndFoundServiceProvider`, and `WebsiteServiceProvider`.

---

## 21. Test vs Real Runtime Difference

```text
TEST_COMPOSITION_SOURCE=Explicit CLI environment variable (CAMPUSHUB_OPTIONAL_PACKAGES=student,lost_and_found,website) or test helper override
REAL_RUNTIME_COMPOSITION_SOURCE=.env file (where CAMPUSHUB_OPTIONAL_PACKAGES was missing, defaulting to '')

SAME=NO (Before Fix) / YES (After Fix)
```

---

## 22. CLI vs Web Runtime Difference

The live web server is served via `php artisan serve` (`127.0.0.1:8000`), which monitors `.env` modification timestamps (`filemtime(base_path('.env'))`) and spawns `/usr/bin/php8.4 -S 127.0.0.1:8000`. Once `.env` was updated with `CAMPUSHUB_OPTIONAL_PACKAGES=student,lost_and_found,website`, both CLI and Web processes read the identical `.env` file and resolve the exact same composition (`CLI_MATCHES_WEB_RUNTIME = YES`).

---

## 23. Root Cause

`CAMPUSHUB_OPTIONAL_PACKAGES` was never added to the local `.env` file. Consequently, `env('CAMPUSHUB_OPTIONAL_PACKAGES', '')` in `config/campushub.php` evaluated to `''` (Foundation-only mode) in the real application, while automated test runs passed because they supplied `CAMPUSHUB_OPTIONAL_PACKAGES` via inline CLI environment variables.

---

## 24. Remediation Performed

Added the single configuration line to `.env`:
```env
CAMPUSHUB_OPTIONAL_PACKAGES=student,lost_and_found,website
```
No production code or architectural logic required modification.

---

## 25. Runtime Matrix

Verified all 5 required compositions:

| Matrix | `CAMPUSHUB_OPTIONAL_PACKAGES` | Resolved Enabled | Total Routes | Home Sections | Header Nav |
|---|---|---|---|---|---|
| **A** | `""` | `[]` | 69 | `[]` | `[]` |
| **B** | `"website"` | `["website"]` | 70 | `["website_hero","website_features","website_announcements"]` | `["website_home","website_about"]` |
| **C** | `"student"` | `["student"]` | 81 | `[]` | `[]` |
| **D** | `"student,lost_and_found"` | `["student","lost_and_found"]` | 102 | `[]` | `[]` |
| **E** | `"student,lost_and_found,website"` | `["student","lost_and_found","website"]` | 105 | `["website_hero","website_features","website_lost_found","website_announcements"]` | `["website_home","website_lost_found","website_about"]` |

---

## 26. Browser Verification

Verified live HTTP responses from `http://127.0.0.1:8000`:
- `GET http://127.0.0.1:8000/` → `HTTP/1.1 200 OK`: Contains `website-hero`, `website-features`, `website-lost-found`, and `website-announcements`; `web-home__empty` is absent.
- `GET http://127.0.0.1:8000/web/locale/en` → `HTTP/1.1 200 OK`: Renders `dir="ltr"`, `"Welcome to University CampusHub"`, `"Recently Found Items"`.
- `GET http://127.0.0.1:8000/web/locale/ar` → `HTTP/1.1 200 OK`: Renders `dir="rtl"`, `"مرحباً بكم في منصة الحرم الجامعي"`, `"المقتنيات التي عُثر عليها حديثاً"`.
- `GET http://127.0.0.1:8000/about` → `HTTP/1.1 200 OK`.
- `GET http://127.0.0.1:8000/lost-found` → `HTTP/1.1 200 OK`.
- `GET http://127.0.0.1:8000/student/login` → `HTTP/1.1 200 OK`.

---

## 27. Database Safety

- Runtime database modified: **NO**
- Destructive DB commands executed: **0**

---

## 28. Git Verification

- `.env` is git-ignored; worktree remains clean of unintended changes.
- `git diff --check`: **PASS** (0 errors).
- No-Undo Protocol (Rule 11): Strictly respected.

---

## 29. Final Effective Composition

The real application now runs with:
- `CAMPUSHUB_OPTIONAL_PACKAGES=student,lost_and_found,website`
- Resolved composition: `["student", "lost_and_found", "website"]`
- Active routes: **105**

---

## 30. Final Verdict

**CERTIFIED.** All optional packages (`Student`, `LostAndFound`, and `Website`) and their cross-package integrations are active, registered, booted, and verified in both the CLI and the live web application.

---

```text
=== BEGIN OPTIONAL PACKAGE RUNTIME ACTIVATION ===

STEP_STATUS=COMPLETED

STUDENT_INSTALLED=YES
LOST_FOUND_INSTALLED=YES
WEBSITE_INSTALLED=YES

RAW_OPTIONAL_PACKAGES_ENV=student,lost_and_found,website
CONFIG_OPTIONAL_PACKAGES=student,lost_and_found,website

STUDENT_ENABLED=YES
LOST_FOUND_ENABLED=YES
WEBSITE_ENABLED=YES

RESOLVED_COMPOSITION=student,lost_and_found,website

STUDENT_PROVIDER_REGISTERED=YES
LOST_FOUND_PROVIDER_REGISTERED=YES
WEBSITE_PROVIDER_REGISTERED=YES

STUDENT_PROVIDER_BOOTED=YES
LOST_FOUND_PROVIDER_BOOTED=YES
WEBSITE_PROVIDER_BOOTED=YES

WEBSITE_HERO_REGISTERED=YES
WEBSITE_FEATURES_REGISTERED=YES
WEBSITE_ANNOUNCEMENTS_REGISTERED=YES
WEBSITE_LOST_FOUND_REGISTERED=YES

PUBLIC_READ_CONTRACT_BOUND=YES

FOUNDATION_ROUTES=69
STUDENT_ROUTES=12
LOST_FOUND_ROUTES=21
WEBSITE_ROUTES=3
TOTAL_ROUTES=105

CONFIG_CACHED=NO
ROUTE_CACHED=NO
AUTOLOAD_STALE=NO

TEST_COMPOSITION_MATCHES_RUNTIME=YES
CLI_MATCHES_WEB_RUNTIME=YES

ROOT_CAUSE=MISSING_CAMPUSHUB_OPTIONAL_PACKAGES_IN_DOTENV

FIX_APPLIED=SET_CAMPUSHUB_OPTIONAL_PACKAGES_IN_DOTENV

FINAL_OPTIONAL_PACKAGES=student,lost_and_found,website
FINAL_RESOLVED_COMPOSITION=student,lost_and_found,website

REAL_HOME_PAGE_VERIFIED=YES

RUNTIME_DATABASE_MODIFIED=NO
DESTRUCTIVE_DB_COMMANDS=0

GIT_DIFF_CHECK=PASS

BLOCKERS=0
RUNTIME_ACTIVATION_CERTIFIED=YES

=== END OPTIONAL PACKAGE RUNTIME ACTIVATION ===
```
