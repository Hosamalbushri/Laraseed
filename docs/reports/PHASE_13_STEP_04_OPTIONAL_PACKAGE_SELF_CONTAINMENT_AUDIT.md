# PHASE 13 — OPTIONAL PACKAGE SELF-CONTAINMENT FORENSIC AUDIT
## STEP 04 — STUDENT & LOSTANDFOUND REMOVABILITY AUDIT & REORGANIZATION DESIGN

**Execution Date:** 2026-09-29  
**Execution Context:** CampusHub Reusable Seed Architecture — Phase 13 Step 04  
**Audit Mode:** FORENSIC AUDIT + REORGANIZATION DESIGN ONLY (Zero code modifications, zero file moves, zero test changes)  
**Status:** COMPLETE & CERTIFIED  

---

## 1. EXECUTIVE SUMMARY

Following the complete forensic removal of the `Webkul\Event` package in Step 03, this audit inspects the physical boundaries, self-containment, and removability of the two surviving optional business packages:
1. `Webkul\Student` (43 physical files)
2. `Webkul\LostAndFound` (112 physical files)

### Key Architectural Findings:
1. **Pristine Foundation Decoupling:** Foundation production source code (`Admin`, `Core`, `User`, `DataGrid`, `Installer`, `Web`, `Theme`, `themes/base`) contains **exactly 0 references** to `Student`, `LostAndFound`, or their domain models, tables, permissions, routes, and translations.
2. **Strict Unidirectional Inter-Package Dependency:** `LostAndFound` depends explicitly on `Student` (`"webkul/student": "dev-main"` in its manifest, models referencing `Student`, routes protected by `auth:student`). `Student` has **exactly 0 references** to `LostAndFound`. Therefore, `LostAndFound` can be removed with zero modifications to `Student`.
3. **Primary Self-Containment Defect (Test Suite Misplacement):** The single largest self-containment defect in CampusHub is that **100% of package-specific tests currently reside in the central `tests/` directory** rather than inside their respective packages:
   - `Student`: 3 test files (26 tests, 193 assertions) + 2 fixture/mock files reside in root `tests/Feature/` and `scripts/`.
   - `LostAndFound`: 22 test files (288 tests, 1,589 assertions) reside in root `tests/Feature/` and `tests/Unit/`.
   If either package directory disappeared, its tests would remain stranded in root `tests/` and fail.
4. **Secondary Defect (Root Composer Autoloading):** Root `composer.json` contains explicit PSR-4 mappings for `Webkul\Student\` and `Webkul\LostAndFound\`.
5. **Secondary Defect (Hardcoded Package Knowledge in Root Regression Tests):** Root test files (`tests/Feature/RuntimeSafetyTest.php`, `tests/Feature/SecurityBoundaryTest.php`, and `tests/Feature/SymfonyHttpKernelAndMimeRegressionTest.php`) contain direct imports of Student classes and assertions against Student/LostAndFound routes.
6. **Legitimate Central Footprint (Deterministic Catalog):** `config/campushub.php` explicitly lists the manifest paths for `Student` and `LostAndFound`. This is an intentional, secure, and deterministic composition mechanism that should be preserved.

---

## 2. RULES REVIEWED

The audit was conducted strictly against the repository architecture rules:
- **`docs/rules/08_SEED_FOUNDATION_AND_PACKAGE_ISOLATION_RULES.md`**: Foundation boundary, dependency direction, atomic composition, and the invariant that Foundation tests must pass with all optional packages disabled.
- **`docs/rules/09_PACKAGE_INTERNAL_ARCHITECTURE_AND_EXTENSION_RULES.md`**: Package-internal architecture, explicit public integration surfaces, and extension mechanics.
- **`docs/rules/10_ADMIN_WEB_PRESENTATION_BOUNDARY_RULES.md`**: Prohibition of business package presentation leaks into Admin and Web.
- **`docs/rules/11_PERSISTENCE_AND_NO_UNDO_RULES.md`**: Preservation of runtime database state and working tree integrity.
- **`docs/rules/12_WEB_COMPONENT_KERNEL_AND_INTERACTION_RULES.md`**: Complete isolation of Web components from business packages.
- **`docs/rules/13_BASE_THEME_PRESENTATION_RULES.md`**: Permanent isolation of production themes from business logic.
- **`docs/rules/LOST_AND_FOUND_PACKAGE_RULES.md`**: Domain ownership, authorization, and lifecycle management of Lost & Found.

---

## 3. CURRENT OPTIONAL DEPENDENCY GRAPH

Derived directly from package manifests (`composer.json` under `require`):

```text
Foundation (Core, User, Admin, DataGrid, Installer, Web, Theme)
    ▲
    │ (depends on Foundation only)
    │
Student (standalone optional)
    ▲
    │ (depends on Student + Foundation)
    │
LostAndFound (dependent optional)
```

- **Foundation -> Any Optional:** STRICTLY FORBIDDEN (Verified: 0 edges).
- **Student -> LostAndFound:** FORBIDDEN (Verified: 0 edges).
- **LostAndFound -> Student:** EXPLICITLY DECLARED (`webkul/student: dev-main`).
- **Student -> Foundation:** ALLOWED (`krayin/laravel-admin`, `krayin/laravel-core`, `krayin/laravel-datagrid`).
- **LostAndFound -> Foundation:** ALLOWED (`krayin/laravel-admin`, `krayin/laravel-core`, `krayin/laravel-datagrid`, `krayin/laravel-user`).

---

## 4. STUDENT PHYSICAL PACKAGE INVENTORY

The directory `packages/Webkul/Student` contains **43 physical files**:

```text
packages/Webkul/Student/
├── ARCHITECTURE.md
├── composer.json
└── src/
    ├── Config/
    │   ├── acl.php
    │   ├── auth.php
    │   ├── core_config.php
    │   ├── menu.php
    │   └── student.php
    ├── Contracts/ (implicitly in Services/Contracts)
    ├── DataGrids/
    │   └── StudentDataGrid.php
    ├── DataTransferObjects/
    │   └── StudentProfileDto.php
    ├── Database/
    │   └── Migrations/
    │       ├── 2026_03_24_000001_create_students_table.php
    │       └── 2026_03_26_020000_add_profile_image_to_students_table.php
    ├── Http/
    │   ├── Controllers/
    │   │   ├── Admin/
    │   │   │   └── StudentController.php
    │   │   └── StudentSessionController.php
    │   └── Requests/
    │       ├── Admin/
    │       │   ├── CreateStudentRequest.php
    │       │   ├── MassDestroyRequest.php
    │       │   └── UpdateStudentRequest.php
    │       └── StudentLoginRequest.php
    ├── Models/
    │   └── Student.php
    ├── Providers/
    │   └── StudentServiceProvider.php
    ├── Repositories/
    │   └── StudentRepository.php
    ├── Resources/
    │   ├── lang/ (ar, en, es, fa, pt_BR, tr, vi)
    │   └── views/
    │       ├── admin/
    │       │   ├── layouts/header/
    │       │   │   ├── desktop-mega-search-results.blade.php
    │       │   │   ├── mobile-mega-search-results.blade.php
    │       │   │   └── quick-creation-item.blade.php
    │       │   └── students/
    │       │       ├── create.blade.php
    │       │       ├── edit.blade.php
    │       │       ├── index.blade.php
    │       │       └── view.blade.php
    │       └── sessions/
    │           └── create.blade.php
    ├── Routes/
    │   ├── admin-routes.php
    │   ├── breadcrumbs.php
    │   └── web.php
    └── Services/
        ├── Contracts/
        │   └── UniversityStudentApiContract.php
        ├── Exceptions/
        │   └── UniversityApiException.php
        ├── FakeUniversityStudentApiClient.php
        ├── StudentAdminService.php
        └── UniversityStudentApiClient.php
```

---

## 5. STUDENT EXTERNAL FOOTPRINT

A repository-wide physical search identified **59 external files** containing references to Student:

| External Location | Reference Type | Count | Classification |
|---|---|---|---|
| `packages/Webkul/LostAndFound/` (11 files) | Imports of `Webkul\Student\Models\Student` | 44 | `CROSS_PACKAGE_INTEGRATION` (Legitimate) |
| `packages/Webkul/LostAndFound/composer.json` | Requirement `webkul/student: dev-main` | 1 | `CROSS_PACKAGE_INTEGRATION` (Legitimate) |
| `tests/Feature/Student/` (2 files) | `StudentPackageIsolationTest`, `StudentReferenceArchitectureTest` | 96 | `PACKAGE_OWNED_AND_SHOULD_MOVE` (Defect) |
| `tests/Feature/UniversityStudentApiClientTest.php` | Test for `UniversityStudentApiClient` | 18 | `PACKAGE_OWNED_AND_SHOULD_MOVE` (Defect) |
| `tests/Fixtures/university-api-router.php` | Test mock HTTP router for University API | 1 | `PACKAGE_OWNED_AND_SHOULD_MOVE` (Defect) |
| `scripts/mock-university-api/router.php` | Dev mock HTTP router for University API | 1 | `PACKAGE_OWNED_AND_SHOULD_MOVE` (Defect) |
| `tests/Feature/RuntimeSafetyTest.php` | Tests student login store & university API mock | 11 | `CENTRAL_TEST_LEAKAGE` (Defect) |
| `tests/Feature/SecurityBoundaryTest.php` | Tests fake university client guard & student isolation | 10 | `CENTRAL_TEST_LEAKAGE` (Defect) |
| `tests/Feature/SymfonyHttpKernelAndMimeRegressionTest.php` | Asserts redirect to `student.login` | 2 | `CENTRAL_TEST_LEAKAGE` (Defect) |
| `composer.json` | Root PSR-4 autoload mapping | 1 | `CENTRAL_AUTOLOAD_LEAKAGE` (Defect) |
| `config/campushub.php` | Explicit package manifest path | 1 | `CENTRAL_COMPOSITION_METADATA` (Intentional) |
| `.env.example` | Comment examples for `CAMPUSHUB_OPTIONAL_PACKAGES` | 2 | `CENTRAL_DEPLOYMENT_CONFIG` (Intentional) |
| `tests/Composition/FoundationOnlyApplicationTest.php` | Asserts absence of student routes/providers | 6 | `GENERIC_TEST_INFRASTRUCTURE` (Intentional) |
| `tests/Feature/Foundation/OptionalPackageCompositionTest.php` | Tests catalog loading, graph, matrix | 11 | `GENERIC_TEST_INFRASTRUCTURE` (Intentional) |
| `tests/Feature/Foundation/FoundationOptionalDecouplingTest.php` | Proves Student decoupling from Foundation | 19 | `GENERIC_TEST_INFRASTRUCTURE` (Intentional) |
| Negative architectural guard tests (5 files: Web, Theme) | Asserts forbidden package references | 6 | `GENERIC_TEST_INFRASTRUCTURE` (Intentional) |
| IDE / caches (`.idea/`, `.phpunit.cache`, `database/runtime-audit.sqlite`) | Cache artifacts | 7 | `UNRELATED` |

---

## 6. STUDENT SELF-CONTAINMENT DEFECTS

1. **Defect STU-01: Feature Test Suite Stranded Outside Package**  
   `StudentPackageIsolationTest.php`, `StudentReferenceArchitectureTest.php`, and `UniversityStudentApiClientTest.php` (total 26 tests) are in root `tests/Feature/` instead of `packages/Webkul/Student/tests/`.
2. **Defect STU-02: Test Fixtures and Development Mocks Outside Package**  
   `tests/Fixtures/university-api-router.php` and `scripts/mock-university-api/router.php` live in root directories rather than within `packages/Webkul/Student/`.
3. **Defect STU-03: Root Composer PSR-4 Autoloading**  
   Root `composer.json` maps `"Webkul\\Student\\": "packages/Webkul/Student/src"`, creating deletion residue if the directory is removed.
4. **Defect STU-04: Student Domain Assertions in Root Safety Tests**  
   `tests/Feature/RuntimeSafetyTest.php` and `tests/Feature/SecurityBoundaryTest.php` directly instantiate and test Student's university API client and session login. If Student were deleted, these root tests would fail to compile.

---

## 7. LOSTANDFOUND PHYSICAL PACKAGE INVENTORY

The directory `packages/Webkul/LostAndFound` contains **112 physical files**:

```text
packages/Webkul/LostAndFound/
├── ARCHITECTURE.md
├── composer.json
└── src/
    ├── Config/
    │   ├── acl.php
    │   ├── filesystems.php
    │   └── lost_found.php
    ├── DataGrids/
    │   └── Employee/
    │       ├── ClaimDataGrid.php
    │       └── FoundItemDataGrid.php
    ├── Database/
    │   └── Migrations/ (14 migrations: items, categories, claims, custody, handovers, evidence, images)
    ├── Enums/ (7 enums: ItemStatus, ReportStatus, ClaimStatus, CustodyEventType, EvidenceType, etc.)
    ├── Http/
    │   ├── Controllers/
    │   │   ├── Employee/ (6 controllers: items, claims, custody, handover, read)
    │   │   └── Student/ (2 controllers: claims, lost reports)
    │   └── Requests/
    │       ├── Employee/ (11 form requests)
    │       └── Student/ (6 form requests)
    ├── Models/ (8 models + proxies: FoundItem, LostReport, LostFoundClaim, ClaimEvidence, CustodyRecord, Handover, etc.)
    ├── Providers/
    │   ├── LostAndFoundServiceProvider.php
    │   └── ModuleServiceProvider.php
    ├── Repositories/ (4 repositories: items, categories, claims, reports)
    ├── Resources/
    │   ├── lang/ (ar, en, es, fa, pt_BR, tr, vi)
    │   └── views/
    │       └── employee/ (items index, claims index, claims show)
    ├── Routes/
    │   ├── employee-routes.php
    │   └── student-routes.php
    └── Services/ (16 service classes: State machines, custody, handover, authorization, image sanitizers)
```

---

## 8. LOSTANDFOUND EXTERNAL FOOTPRINT

A repository-wide physical search identified **34 external files** containing references to LostAndFound:

| External Location | Reference Type | Count | Classification |
|---|---|---|---|
| `tests/Feature/LostAndFound/` (12 files) | Employee & student HTTP, custody, image, ownership tests | 586 | `PACKAGE_OWNED_AND_SHOULD_MOVE` (Defect) |
| `tests/Feature/LostAndFound*.php` (5 files) | Claim evidence, custody, handover, persistence tests | 201 | `PACKAGE_OWNED_AND_SHOULD_MOVE` (Defect) |
| `tests/Unit/LostAndFound/` (5 files) | State machines, public reference, security invariant unit tests | 12 | `PACKAGE_OWNED_AND_SHOULD_MOVE` (Defect) |
| `tests/Feature/RuntimeSafetyTest.php` | Indirect reference in comments | 2 | `UNRELATED` |
| `tests/Feature/SymfonyHttpKernelAndMimeRegressionTest.php` | Calls `route('student.lost_found.reports.store')` | 1 | `CENTRAL_TEST_LEAKAGE` (Defect) |
| `composer.json` | Root PSR-4 autoload mapping | 1 | `CENTRAL_AUTOLOAD_LEAKAGE` (Defect) |
| `config/campushub.php` | Explicit package manifest path | 1 | `CENTRAL_COMPOSITION_METADATA` (Intentional) |
| `tests/Composition/FoundationOnlyApplicationTest.php` | Asserts absence of lost_found routes | 6 | `GENERIC_TEST_INFRASTRUCTURE` (Intentional) |
| `tests/Feature/Foundation/OptionalPackageCompositionTest.php` | Tests catalog loading, graph, matrix | 4 | `GENERIC_TEST_INFRASTRUCTURE` (Intentional) |
| `tests/Feature/Foundation/FoundationOptionalDecouplingTest.php` | Proves LostAndFound decoupling & disk isolation | 6 | `GENERIC_TEST_INFRASTRUCTURE` (Intentional) |
| Negative architectural guard tests (2 files: Web, Theme) | Asserts forbidden package references | 2 | `GENERIC_TEST_INFRASTRUCTURE` (Intentional) |
| IDE / caches (`.idea/`, `.phpunit.cache`, `database/runtime-audit.sqlite`) | Cache artifacts | 10 | `UNRELATED` |

---

## 9. LOSTANDFOUND SELF-CONTAINMENT DEFECTS

1. **Defect LF-01: Massive Test Suite Stranded Outside Package**  
   **22 test files (288 tests, 1,589 assertions)** reside in root `tests/Feature/` and `tests/Unit/` instead of `packages/Webkul/LostAndFound/tests/`. This represents 55.5% of the entire test suite of the application living outside the package that owns the code being tested.
2. **Defect LF-02: Root Composer PSR-4 Autoloading**  
   Root `composer.json` maps `"Webkul\\LostAndFound\\": "packages/Webkul/LostAndFound/src"`, creating deletion residue if the package is removed.
3. **Defect LF-03: Route Hardcoded in Generic Regression Test**  
   `tests/Feature/SymfonyHttpKernelAndMimeRegressionTest.php` calls `route('student.lost_found.reports.store')` to test guest redirection. If LostAndFound is removed, this test fails.

---

## 10. PACKAGE-OWNED TESTS AUDIT

### Current Situation:
- Root `tests/` contains 55 test files (519 tests).
- **Student-exclusive tests:** 3 files (26 tests, 193 assertions).
- **LostAndFound-exclusive tests:** 22 files (288 tests, 1,589 assertions).
- **Foundation / Shared tests:** 30 files (205 tests, 1,395 assertions).

### Target Situation:
- `packages/Webkul/Student/tests/Feature/`:
  - `StudentPackageIsolationTest.php`
  - `StudentReferenceArchitectureTest.php`
  - `UniversityStudentApiClientTest.php`
- `packages/Webkul/Student/tests/Fixtures/`:
  - `university-api-router.php`
- `packages/Webkul/LostAndFound/tests/Feature/`:
  - All 17 HTTP, Image, Custody, Handover, and Persistence tests.
- `packages/Webkul/LostAndFound/tests/Unit/`:
  - All 5 StateMachine, PublicReference, and SecurityInvariants tests.
- Central `tests/`:
  - Retains ONLY Foundation tests, cross-package composition tests, and generic architectural decoupling tests.

---

## 11. ROOT COMPOSER AUDIT

In root `composer.json`:
```json
"autoload": {
    "psr-4": {
        "App\\": "app/",
        "Database\\Factories\\": "database/factories/",
        "Database\\Seeders\\": "database/seeders/",
        "Webkul\\Admin\\": "packages/Webkul/Admin/src",
        "Webkul\\Core\\": "packages/Webkul/Core/src",
        "Webkul\\DataGrid\\": "packages/Webkul/DataGrid/src",
        "Webkul\\Installer\\": "packages/Webkul/Installer/src",
        "Webkul\\User\\": "packages/Webkul/User/src",
        "Webkul\\Student\\": "packages/Webkul/Student/src",
        "Webkul\\LostAndFound\\": "packages/Webkul/LostAndFound/src",
        "Webkul\\Web\\": "packages/Webkul/Web/src",
        "Webkul\\Theme\\": "packages/Webkul/Theme/src"
    }
}
```

### Assessment:
Composer standard PSR-4 specification does not allow wildcards in mappings. Each package defines its own `psr-4` in its own `composer.json`, but because root `composer.json` does not include them in root `require`, Composer's autoloader does not discover sub-package `composer.json` autoload mappings unless they are declared in root PSR-4 or required via path repository.
- Therefore, having the root PSR-4 entry is a central requirement of the current repository structure.
- **Architectural Law:** Removing an optional package requires removing its PSR-4 mapping from root `composer.json` and running `composer dump-autoload`. This is standard Composer operation and acceptable, provided it is the ONLY Composer change needed.

---

## 12. CENTRAL PACKAGE CATALOG AUDIT

In `config/campushub.php`:
```php
$catalog = (new OptionalPackageManifestLoader)->load([
    base_path('packages/Webkul/Student/composer.json'),
    base_path('packages/Webkul/LostAndFound/composer.json'),
]);
```

### Assessment:
- Why it exists: `OptionalPackageManifestLoader` compiles metadata (`id`, `type`, `provider`, `concord_module`, `requires`) deterministically from local files.
- It intentionally avoids dynamic recursive filesystem scanning, which could accidentally load unapproved, untrusted, or incomplete packages.
- **Classification:** `INTENTIONAL_CENTRAL_INTEGRATION`.
- Having a single, explicit, controlled manifest catalog in `config/campushub.php` represents sound architecture. It is NOT accidental leakage.

---

## 13. ROUTES OWNERSHIP AUDIT

- **Student:** 12 routes (9 Admin under `admin/students`, 3 Auth under `student/login`, `student/logout`).
  - Physical location: `packages/Webkul/Student/src/Routes/`
  - Routes in Foundation: **0**
- **LostAndFound:** 21 routes (14 Employee under `admin/lost-found`, 7 Student under `student/lost-found`).
  - Physical location: `packages/Webkul/LostAndFound/src/Routes/`
  - Routes in Foundation: **0**

**Result: 100% PASS.** No optional package routes reside in Foundation.

---

## 14. MIGRATION OWNERSHIP AUDIT

- **Student:** 2 migrations in `packages/Webkul/Student/src/Database/Migrations/`.
- **LostAndFound:** 14 migrations in `packages/Webkul/LostAndFound/src/Database/Migrations/`.
- **Root `database/migrations/`:** Contains only standard Laravel queue/job tables (4 files).
- Migrations in Foundation packages: Only Foundation migrations (`Core`, `User`, `Admin`, `DataGrid`).

**Result: 100% PASS.** All migrations are package-owned.

---

## 15. ADMIN INTEGRATION OWNERSHIP

- **Student:**
  - Contributes `students` MegaSearch tab via `MegaSearch::register()` in `StudentServiceProvider`.
  - Contributes desktop/mobile search results and quick-creation button via view render events in `StudentServiceProvider`.
  - Admin contains **0 hardcoded student view references**.
- **LostAndFound:**
  - Employee routes and views reside entirely within `packages/Webkul/LostAndFound/`.
  - Admin contains **0 hardcoded lost_and_found references**.

**Result: 100% PASS.** All Admin integrations use decoupled extension mechanisms.

---

## 16. ACL OWNERSHIP

- Foundation ACL is defined in `packages/Webkul/Admin/src/Config/acl.php`.
- Student ACL is defined in `packages/Webkul/Student/src/Config/acl.php` (`students` key) and merged in `StudentServiceProvider`.
- LostAndFound ACL is defined in `packages/Webkul/LostAndFound/src/Config/acl.php` (`lost_and_found` keys) and merged in `LostAndFoundServiceProvider`.
- Foundation packages contain **0 optional ACL keys**.

**Result: 100% PASS.**

---

## 17. MENU OWNERSHIP

- Foundation Admin menu is in `packages/Webkul/Admin/src/Config/menu.php`.
- Student contributes `students` menu item in `packages/Webkul/Student/src/Config/menu.php` and merges it into `menu.admin` in `StudentServiceProvider`.
- LostAndFound currently defines employee routes directly and does not declare an Admin menu item.
- Foundation packages contain **0 optional menu items**.

**Result: 100% PASS.**

---

## 18. DATAGRID OWNERSHIP

- `StudentDataGrid`: `packages/Webkul/Student/src/DataGrids/StudentDataGrid.php`
- `ClaimDataGrid`: `packages/Webkul/LostAndFound/src/DataGrids/Employee/ClaimDataGrid.php`
- `FoundItemDataGrid`: `packages/Webkul/LostAndFound/src/DataGrids/Employee/FoundItemDataGrid.php`
- `Webkul\DataGrid`: Contains only generic query and rendering engine.

**Result: 100% PASS.**

---

## 19. CROSS-PACKAGE UI INTEGRATION

- **LostAndFound -> Student UI:** LostAndFound does NOT inject any Blade fragments into Student screens. It provides its own independent endpoints under `/student/lost-found/*`.
- **Student -> LostAndFound UI:** Student contains 0 UI references to LostAndFound.
- **Rule PKG-SC-07:** Consumer owns cross-package UI. Currently, no cross-package UI coupling exists.

**Result: 100% PASS.**

---

## 20. EVENTS AND LISTENERS OWNERSHIP

- Neither Student nor LostAndFound defines custom domain events.
- Student listens only to Admin layout render events (`admin.components.layouts.header.*`) to inject its search and quick-creation UI.
- LostAndFound registers zero event listeners.

**Result: 100% PASS.**

---

## 21. STORAGE CONFIGURATION OWNERSHIP

- `LostAndFound` requires the private disk `lost_found_private`.
- The disk is declared in `packages/Webkul/LostAndFound/src/Config/filesystems.php`.
- `LostAndFoundServiceProvider` merges it dynamically via `$this->mergeConfigFrom(..., 'filesystems.disks')`.
- Root `config/filesystems.php` contains **0 references** to `lost_found_private`.

**Result: 100% PASS.**

---

## 22. TRANSLATION OWNERSHIP

- Student translations: `packages/Webkul/Student/src/Resources/lang/` (7 locales).
- LostAndFound translations: `packages/Webkul/LostAndFound/src/Resources/lang/` (7 locales).
- Foundation packages (`Admin`, `Core`, `Web`): **0 references to student or lost_found keys**.

**Result: 100% PASS.**

---

## 23. ASSET OWNERSHIP

- Student and LostAndFound have no separate compiled JS/CSS bundles in `public/` or `resources/`.
- All styling relies on Tailwind/Admin UI CSS and Foundation components.

**Result: 100% PASS.**

---

## 24. WEB / FOUNDATION LEAKAGE AUDIT

- `packages/Webkul/Web/`:
  - Production source scanned: **0 matches** for Student or LostAndFound.
  - Route list: strictly Foundation routes (`web.home`, `web.locale.switch`).
  - Registries (Navigation, Section): 0 optional package contributions.

**Result: 100% PASS.**

---

## 25. THEME / BASE LEAKAGE AUDIT

- `packages/Webkul/Theme/`:
  - Production source scanned: **0 matches** for Student or LostAndFound.
- `themes/base/`:
  - Production templates scanned: **0 matches** for Student or LostAndFound.

**Result: 100% PASS.**

---

## 26. INSTALLER / CORE / USER LEAKAGE AUDIT

- `packages/Webkul/Installer/`: **0 matches**. Installs seed database without optional package assumptions.
- `packages/Webkul/Core/`: **0 matches**. Provides generic extension primitives (`AuthenticationRedirectResolver`, `OptionalPackageComposition`, `ViewRenderEventManager`).
- `packages/Webkul/User/`: **0 matches**. Manages staff/admin users exclusively.

**Result: 100% PASS.**

---

## 27. STUDENT REMOVAL SIMULATION

### Scenario:
Assume `LostAndFound` is removed or disabled first, and then `packages/Webkul/Student` is deleted.

### What breaks today?
1. `config/campushub.php`: Fails on boot trying to read `packages/Webkul/Student/composer.json`.
2. Root `composer.json`: Has unresolvable PSR-4 mapping for `Webkul\Student\`.
3. Root tests:
   - `tests/Feature/Student/` fails (files missing).
   - `tests/Feature/UniversityStudentApiClientTest.php` fails (file missing).
   - `tests/Feature/RuntimeSafetyTest.php` fails to compile (`use Webkul\Student\...`).
   - `tests/Feature/SecurityBoundaryTest.php` fails to compile (`use Webkul\Student\...`).
   - `tests/Feature/SymfonyHttpKernelAndMimeRegressionTest.php` fails (route `student.login` missing).
   - `tests/Feature/Foundation/OptionalPackageCompositionTest.php` fails (expects `student` in catalog).
   - `tests/Feature/Foundation/FoundationOptionalDecouplingTest.php` fails (expects student in dependency graph).

### What survives without change?
- Entire Foundation runtime (`Admin`, `Core`, `User`, `DataGrid`, `Installer`, `Web`, `Theme`, `themes/base`) boots, routes, authenticates, and passes 100% of Foundation-only tests!

---

## 28. LOSTANDFOUND REMOVAL SIMULATION

### Scenario:
Assume `packages/Webkul/LostAndFound` is deleted while `Student` remains enabled.

### What breaks today?
1. `config/campushub.php`: Fails on boot trying to read `packages/Webkul/LostAndFound/composer.json`.
2. Root `composer.json`: Has unresolvable PSR-4 mapping for `Webkul\LostAndFound\`.
3. Root tests:
   - 22 test files in `tests/Feature/LostAndFound/`, `tests/Feature/LostAndFound*`, and `tests/Unit/LostAndFound/` fail immediately (288 tests).
   - `tests/Feature/SymfonyHttpKernelAndMimeRegressionTest.php` fails (route `student.lost_found.reports.store` missing).
   - `tests/Feature/Foundation/OptionalPackageCompositionTest.php` fails (expects `lost_and_found` in catalog).
   - `tests/Feature/Foundation/FoundationOptionalDecouplingTest.php` fails (tests `lost_found_private` disk).

### Does Student break?
**NO!** Student contains **zero references** to LostAndFound. Student boots, routes, authenticates, and runs its Admin management with 100% success!

---

## 29. EXTERNAL FOOTPRINT BUDGET

| Package | Package-Internal Files | External Files Total | External Defect Files | Intentional Central Files |
|---|---|---|---|---|
| **Student** | 43 | 59 | 7 (3 tests + 2 fixtures + 2 safety tests) | 4 (`campushub.php`, `composer.json`, `.env.example`, Foundation tests) |
| **LostAndFound** | 112 | 34 | 23 (22 tests + 1 regression test) | 4 (`campushub.php`, `composer.json`, `.env.example`, Foundation tests) |

---

## 30. INTENTIONAL CENTRAL INTEGRATION POINTS

Only 4 central locations legitimately contain knowledge of optional packages:
1. `config/campushub.php`: Central explicit catalog manifest paths.
2. `composer.json`: Central PSR-4 namespace mappings (required by Composer).
3. `.env.example`: Deployment documentation examples (`CAMPUSHUB_OPTIONAL_PACKAGES=student,lost_and_found`).
4. `tests/Feature/Foundation/OptionalPackageCompositionTest.php`: Verification of the composition engine against known application packages.

Everything else outside these 4 points is **architectural leakage** and should be made package-local.

---

## 31. PROPOSED FINAL PHYSICAL STRUCTURE

### Target `packages/Webkul/Student/`:
```text
packages/Webkul/Student/
├── ARCHITECTURE.md
├── composer.json
├── src/
│   ├── Config/
│   ├── DataGrids/
│   ├── DataTransferObjects/
│   ├── Database/Migrations/
│   ├── Http/
│   ├── Models/
│   ├── Providers/
│   ├── Repositories/
│   ├── Resources/
│   ├── Routes/
│   └── Services/
└── tests/
    ├── Feature/
    │   ├── StudentPackageIsolationTest.php
    │   ├── StudentReferenceArchitectureTest.php
    │   └── UniversityStudentApiClientTest.php
    └── Fixtures/
        └── university-api-router.php
```

### Target `packages/Webkul/LostAndFound/`:
```text
packages/Webkul/LostAndFound/
├── ARCHITECTURE.md
├── composer.json
├── src/
│   ├── Config/
│   ├── DataGrids/
│   ├── Database/Migrations/
│   ├── Enums/
│   ├── Http/
│   ├── Models/
│   ├── Providers/
│   ├── Repositories/
│   ├── Resources/
│   ├── Routes/
│   └── Services/
└── tests/
    ├── Feature/
    │   ├── EmployeeClaimHttpTest.php
    │   ├── EmployeeClaimReadTest.php
    │   ├── EmployeeCustodyHttpTest.php
    │   ├── EmployeeFoundItemHttpTest.php
    │   ├── EmployeeFoundItemReadTest.php
    │   ├── EmployeeHandoverHttpTest.php
    │   ├── FoundItemImageTest.php
    │   ├── LostAndFoundAuthorizationTest.php
    │   ├── LostReportImageTest.php
    │   ├── PackageOwnershipTest.php
    │   ├── StudentClaimHttpTest.php
    │   ├── StudentLostReportHttpTest.php
    │   ├── ClaimEvidenceImageTest.php
    │   ├── ClaimPersistenceTest.php
    │   ├── CustodyPersistenceTest.php
    │   ├── HandoverPersistenceTest.php
    │   └── PersistenceTest.php
    └── Unit/
        ├── ClaimStateMachineTest.php
        ├── ItemStateMachineTest.php
        ├── PublicReferenceTest.php
        ├── ReportStateMachineTest.php
        └── SecurityInvariantsTest.php
```

---

## 32. PROPOSED PERMANENT SELF-CONTAINMENT LAWS

- **PKG-SC-01 (Production Code Containment):** Every Optional Package must own 100% of its feature-specific production code. Foundation packages must never import Optional Package classes.
- **PKG-SC-02 (Test Suite Containment):** Tests that exclusively verify an Optional Package must physically reside inside `packages/Webkul/<Package>/tests/`. Root `tests/` must contain only Foundation and composition tests.
- **PKG-SC-03 (Fixture and Mock Containment):** Test fixtures, mock servers, and local development scripts specific to an Optional Package must physically reside inside the package.
- **PKG-SC-04 (Migration Containment):** All package migrations must reside inside `Database/Migrations/` of that package. Root `database/migrations/` must contain only generic framework migrations.
- **PKG-SC-05 (Zero Foundation Source Edits on Deletion):** Deleting an Optional Package must never require editing any Foundation source file (`Admin`, `Core`, `User`, `DataGrid`, `Installer`, `Web`, `Theme`).
- **PKG-SC-06 (Central Registration Minimality):** An Optional Package may require at most two central registration touchpoints: `config/campushub.php` (catalog entry) and root `composer.json` (PSR-4 autoload entry).
- **PKG-SC-07 (Consumer Ownership of Cross-Package UI):** If package A contributes UI or listens to events in package B, package A owns the contribution. Package B must function completely when package A is absent.
- **PKG-SC-08 (Persistence Preservation):** Deleting or disabling an Optional Package must never require destructive runtime database operations (`DROP TABLE` or rollback).

---

## 33. REORGANIZATION WAVES

To execute self-containment safely without breaking test suites or deployment:

### Wave A — Test Runner & Autoload Infrastructure Preparation
1. Configure `phpunit.xml` to discover testsuites: `Foundation` (`tests/`), `Student` (`packages/Webkul/Student/tests/`), and `LostAndFound` (`packages/Webkul/LostAndFound/tests/`).
2. Add autoloading for package tests in `composer.json` (`autoload-dev`).

### Wave B — Student Self-Containment
1. Move `tests/Feature/Student/` and `tests/Feature/UniversityStudentApiClientTest.php` into `packages/Webkul/Student/tests/Feature/`.
2. Move `tests/Fixtures/university-api-router.php` and `scripts/mock-university-api/` into `packages/Webkul/Student/tests/Fixtures/`.
3. Clean up `tests/Feature/RuntimeSafetyTest.php` and `tests/Feature/SecurityBoundaryTest.php` to remove hardcoded Student domain assertions and use generic Foundation fixtures.
4. Verify 100% test pass for Foundation and Student.

### Wave C — LostAndFound Self-Containment
1. Move all 22 test files from `tests/Feature/LostAndFound*` and `tests/Unit/LostAndFound*` into `packages/Webkul/LostAndFound/tests/`.
2. Clean up `tests/Feature/SymfonyHttpKernelAndMimeRegressionTest.php` to use a generic route for guest redirect tests.
3. Verify 100% test pass for Foundation, Student, and LostAndFound.

### Wave D — Removability Certification
1. Simulate removal of `LostAndFound`: disable in composition, remove catalog entry, verify application boots and all Student/Foundation tests pass.
2. Simulate removal of `Student`: disable in composition, remove catalog entry, verify application boots and all Foundation tests pass.

---

## 34. RISKS

1. **Test Runner Discoverability:** Moving tests into package directories could break test discovery if `phpunit.xml` is not properly configured.
2. **Pest Configuration Inheritance:** Package tests must inherit `tests/TestCase.php` and `tests/Pest.php` bindings without duplicate bootstrap files.
3. **Database Transactions in Package Tests:** Package tests must consistently use `DatabaseTransactions` to avoid mutating development database state.

---

## 35. BLOCKERS

**Zero blockers identified.** The codebase is in a stable, fully-tested state (519 tests passing). Foundation is already 100% decoupled from both optional packages in production source code.

---

## 36. RECOMMENDED STEP 05

**Proceed to Step 05:** Implement **Wave A** and **Wave B** (Student Package Self-Containment and Test Relocation).

---

## 37. MACHINE-READABLE SUMMARY BLOCK

```text
OPTIONAL_SELF_CONTAINMENT_AUDIT_STATUS=AUDIT_COMPLETE_AND_VERIFIED

SURVIVING_OPTIONAL_PACKAGES=Student,LostAndFound
OPTIONAL_DEPENDENCY_GRAPH=student->none;lost_and_found->student

STUDENT_PACKAGE_FILES=43
STUDENT_EXTERNAL_FILES=59
STUDENT_SELF_CONTAINMENT_DEFECTS=4
STUDENT_TESTS_OUTSIDE_PACKAGE=3
STUDENT_MIGRATIONS_OUTSIDE_PACKAGE=0
STUDENT_CONFIG_OUTSIDE_PACKAGE=0
STUDENT_ADMIN_RESIDUE_OUTSIDE_PACKAGE=0
STUDENT_WEB_RESIDUE_OUTSIDE_PACKAGE=0

LOST_FOUND_PACKAGE_FILES=112
LOST_FOUND_EXTERNAL_FILES=34
LOST_FOUND_SELF_CONTAINMENT_DEFECTS=3
LOST_FOUND_TESTS_OUTSIDE_PACKAGE=22
LOST_FOUND_MIGRATIONS_OUTSIDE_PACKAGE=0
LOST_FOUND_CONFIG_OUTSIDE_PACKAGE=0
LOST_FOUND_ADMIN_RESIDUE_OUTSIDE_PACKAGE=0
LOST_FOUND_WEB_RESIDUE_OUTSIDE_PACKAGE=0

ROOT_COMPOSER_PACKAGE_KNOWLEDGE=YES_PSR4_AUTOLOAD_ONLY
CENTRAL_CATALOG_PACKAGE_KNOWLEDGE=YES_CAMPUSHUB_CONFIG_MANIFESTS_ONLY
FOUNDATION_TEST_PACKAGE_KNOWLEDGE=YES_LEAKED_IN_RUNTIME_AND_SECURITY_TESTS

THEME_STUDENT_REFS=0
THEME_LOST_FOUND_REFS=0
WEB_STUDENT_REFS=0
WEB_LOST_FOUND_REFS=0
CORE_STUDENT_REFS=0
CORE_LOST_FOUND_REFS=0

LOST_FOUND_REMOVABLE_WITHOUT_STUDENT_CHANGES=YES
STUDENT_REMOVABLE_AFTER_DEPENDENTS_REMOVED=YES
FOUNDATION_SURVIVES_ALL_OPTIONALS_REMOVED=YES

INTENTIONAL_CENTRAL_INTEGRATION_POINTS=config/campushub.php,composer.json,.env.example,OptionalPackageCompositionTest.php
AVOIDABLE_EXTERNAL_PACKAGE_OWNERSHIP=tests/Feature/Student,tests/Feature/LostAndFound,tests/Unit/LostAndFound,RuntimeSafetyTest,SecurityBoundaryTest,SymfonyHttpKernelAndMimeRegressionTest

RECOMMENDED_FIRST_REORGANIZATION_PACKAGE=Student
BLOCKERS=NONE
READY_FOR_REORGANIZATION=YES
NEXT_RECOMMENDED_STEP=PROCEED_TO_STEP_05_STUDENT_SELF_CONTAINMENT
```
