# Phase 13 Step 02A — Foundation Decoupling Report

Date: 2026-09-29  
Mode: implementation, no package disabling, no undo.

## 1. Rules Read

Read `docs/rules/README.md`, Rules 06–13, the LostAndFound rule, and the Step 01 audit. Rule 08 was selected for the permanent clarification because it already owns Foundation/optional dependency and composition law.

## 2. Phase 13 Step 01 Findings Revalidated

Physical inspection reconfirmed 11 Student business-owning Foundation/root files, one root LostAndFound disk definition, zero Event business references in Foundation, five Event→Student namespace references, eight LostAndFound→Student namespace references, and no reverse Student dependency. Formal provider/Concord/PSR-4 records remain intentionally unchanged for Step 02B.

## 3. Baseline

Before implementation: Laravel 12.61.1; PHP 8.4.24; Composer valid; 120 routes; zero scheduled tasks; 524 tests / 3,404 assertions passed in 17.51s. Pre-existing worktree state was modified `.phpunit.cache/test-results` plus the untracked Step 01 report.

## 4. Student Leakage Before State

The 11 executable/resource files were `bootstrap/app.php`, `config/auth.php`, Admin Handler/provider, two Admin language files, three Admin header views, and two Web language files. Admin's empty Front route additionally held a stale comment. Coupling included the concrete model/guard/provider, `student.login`, `admin.students.*`, Student MegaSearch/quick-create rendering, and translations.

## 5. Root Auth Decoupling

`config/auth.php` now declares only Foundation `user` auth. Student owns `src/Config/auth.php`, and its provider merges the `student` guard and `students` provider before services resolve. Runtime configuration remains unchanged while root source contains zero Student references.

## 6. Guest Redirect Decoupling

Laravel exposes one global guest callback, so Core now provides a small ordered `AuthenticationRedirectResolver` contract/implementation. `bootstrap/app.php` calls only that generic contract. Admin contributes the fallback Admin-login destination; Student contributes the `/student/*` match and `student.login` destination.

## 7. Student Auth Ownership

`StudentServiceProvider` owns guard/provider composition, the Student redirect rule, login rate limiting, routes and authentication client bindings. No database flag or arbitrary loading was introduced. The approach is compatible with configuration caching because package registration operates on Laravel's configuration repository and cache creation boots the composed providers.

## 8. Admin Student Leakage

All precise Student ownership was removed from Admin. Classification: MegaSearch registration/result UI and quick-create moved to Student; exception redirection generalized through the resolver; duplicate Student translations deleted; generic Arabic “person” translations corrected where they had been rendered as Student terminology; the Front route comment was stale residue.

## 9. Student MegaSearch Ownership

Before owner: Admin provider and two Admin header views. After owner: Student provider plus Student desktop/mobile fragments. Generic host: the existing Admin `MegaSearch` registry and view-render events. No second registry was created.

## 10. Student Quick-Create Ownership

Before owner: Admin quick-create host contained a direct Student permission/route/label block. After owner: existing Student quick-create fragment. Generic host: `admin.components.layouts.header.quick_creation` remains in Admin.

## 11. Admin Exception Ownership

Admin's Handler no longer checks the `student` guard or calls `student.login`. It preserves Admin JSON error behavior and honors the redirect already placed on Laravel's `AuthenticationException`, falling back only to the Foundation Admin login.

## 12. Admin Translation Ownership

Removed duplicate Admin keys: `acl.students`, MegaSearch `tabs.students` and `explore-all-students`, `configuration...student-login`, `configuration...university-api`, `settings.menu.students`, and the root `students` tree in English/Arabic. Student already owned equivalent active keys across all seven locales, so no compatibility alias was needed. Stale Student-portal wording was generalized; Arabic CRM `persons` mistranslated as students was restored to generic “persons.”

## 13. Web Student Leakage

The only Web findings were unused `home.student_portal` strings in English and Arabic. No Student code, route, view or model dependency existed.

## 14. Web Translation Cleanup

Both unused `student_portal` keys were deleted. Web production source now has zero Student business terminology under the defined precise scan.

## 15. LostAndFound Filesystem Ownership

`lost_found_private` moved from root `config/filesystems.php` to `LostAndFound/src/Config/filesystems.php`. `LostAndFoundServiceProvider` merges it into `filesystems.disks` during registration.

## 16. Storage Compatibility

The disk name, local driver, `storage/app/lost-found-private` root and `throw => true` behavior are unchanged. `Storage::disk('lost_found_private')` resolves in the focused test. No file, object key, database path or stored data was touched.

## 17. Event Isolation Preservation

No Event production architecture changed. Foundation→Event remains zero. Event remains enabled and its Student relation/subscription dependencies continue to pass.

## 18. DataGrid Manifest

Created `packages/Webkul/DataGrid/composer.json` as `krayin/laravel-datagrid`, declaring only its proven internal dependency on Core and its existing provider/autoload mapping.

## 19. Student Manifest

Declared Admin, Core and DataGrid in addition to existing PHP, Guzzle and Laravel requirements. These match production imports/presentation usage. Student has no optional dependency.

## 20. Event Manifest

Retained Student and Web and added proven Admin, Core and DataGrid dependencies. User is transitively supplied through Admin and is not directly imported by Event production source.

## 21. LostAndFound Manifest

Declared Admin presentation infrastructure, Core, DataGrid, User and Student. Student is required by models/services/routes/schema; User is required for employee/custody identities.

## 22. Web Manifest

Added the proven Core dependency alongside existing Theme. No optional business dependency was added.

## 23. Foundation Manifest Consistency

| Package | Declared internal dependencies | Proven runtime internal dependencies | Match |
| --- | --- | --- | --- |
| Admin | Core, DataGrid, User | Core, DataGrid, User | YES |
| Core | none | none at runtime | YES |
| DataGrid | Core | Core | YES |
| Installer | Core, User | Core, User | YES |
| User | Core | Core | YES |
| Web | Core, Theme | Core, Theme | YES |
| Theme | none | none | YES |
| Student | Admin, Core, DataGrid | Admin, Core, DataGrid | YES |
| Event | Admin, Core, DataGrid, Student, Web | same | YES |
| LostAndFound | Admin, Core, DataGrid, User, Student | same | YES |

Admin's seven obsolete Attribute/Contact/Email/Lead/Product/Tag/UI requirements were removed after confirming no production namespace use. Installer gained its proven Core/User declarations. Theme gained its missing license. Root does not require these path packages, so `composer.lock` required no change.

## 24. Optional Dependency Graph

`Event → Student`; `LostAndFound → Student`; `Student → none optional`. Event and LostAndFound do not depend on each other.

## 25. Static Reference Counts

After-state precise scans: Foundation→Student 0; Foundation→Event 0; Foundation→LostAndFound 0; Web→Student 0; Admin→Student 0; Core→Student 0; User→Student 0; root auth→Student 0; root bootstrap→Student 0; root filesystem→LostAndFound 0. Assets were excluded and formal composition was counted separately.

## 26. Formal Composition References

Eight unchanged registration records remain: three root PSR-4 mappings (Student/Event/LostAndFound), three main-provider registrations, and two Concord module registrations (Event/LostAndFound). They are composition, not business coupling, and belong to Step 02B.

## 27. Root Regression

PASS. `/` remains the single `web.home` route owned by `Webkul\Web\Http\Controllers\HomeController@index`.

## 28. Student Authentication Regression

PASS. Student + university client focused suite: 25 tests / 185 assertions. Runtime safety separately: 19 / 46. `/student/login`, guest redirects, session behavior and university authentication pass. One exploratory combined invocation exposed pre-existing intended-URL session ordering between test files; separated suites and the full canonical suite pass.

## 29. Student Admin Regression

PASS. Student routes, CRUD, DataGrid, ACL/menu/config, MegaSearch and quick-create ownership are covered by the Student and new Foundation tests. The Student tab still resolves `admin.students.search`.

## 30. Event Regression

PASS: 28 tests / 312 assertions.

## 31. LostAndFound Regression

PASS: 275 tests / 1,570 assertions. Private storage compatibility is additionally covered by the new decoupling test.

## 32. Web Regression

PASS: 47 tests / 533 assertions, including WebContext, root, components, registries, SEO and navigation localization.

## 33. Theme/Base Regression

PASS: 49 tests / 378 assertions, including Base rendering, isolation, registry, inheritance and view resolution.

## 34. Localization Regression

PASS. Core/Admin locale-focused suite: 24 tests / 206 assertions. Student/Event locale parity and Web navigation locale checks also pass in package/full suites.

## 35. Foundation Regression

PASS. The broad focused regression command completed 509 tests / 3,398 assertions. Core, User/auth, Admin, DataGrid, Installer, Web, Theme and Base coverage all passed.

## 36. Route Verification

120 routes remain. No package or business route was removed. Root remains Web-owned.

## 37. Schedule Verification

Zero scheduled tasks; no task was added.

## 38. Composer Validation

Root and all ten local package manifests validate. No lock update was required because local package manifests are not root requirements. No external dependency/version changed.

## 39. Full Test Suite

PASS: 530 tests / 3,437 assertions in 18.66s, up by six tests and 33 assertions from baseline.

## 40. Database Verification

No migration was added, no schema command was run, and no production/runtime data was changed. Tests used their established isolated/transactional behavior.

## 41. Files Created

- Foundation decoupling: Core auth resolver contract and implementation.
- Student ownership: `Student/src/Config/auth.php`.
- LostAndFound ownership: `LostAndFound/src/Config/filesystems.php`.
- Package metadata: `DataGrid/composer.json`.
- Test: `tests/Feature/Foundation/FoundationOptionalDecouplingTest.php`.
- Report: this file.

## 42. Files Modified

- Foundation decoupling: `bootstrap/app.php`, `config/auth.php`, `config/filesystems.php`, Core/Admin providers, Admin Handler, three Admin header views, Admin Front route comment, Admin English/Arabic translations, Web English/Arabic translations.
- Student ownership: Student provider.
- LostAndFound ownership: LostAndFound provider.
- Package metadata: Admin, Installer, Student, Event, LostAndFound, Web and Theme manifests.
- Rule: Rule 08.
- Pre-existing/generated: `.phpunit.cache/test-results`; the Step 01 report was already untracked before this step.

## 43. Files Deleted

None.

## 44. Rule Updates

Rule 08 now explicitly prohibits optional identities/redirects/translations/filesystem disks in Foundation, requires optional Admin/Web contributions to originate in the optional package, and requires manifests to declare proven internal dependencies.

## 45. Initial Final Persistence Verification

Before report creation: all precise coupling scans were zero; root was `web.home`; 120 routes; zero schedules; root/local Composer validation passed; `git diff --check` passed; implementation files remained present.

## 46. Post-Report Persistence Verification

Recorded after this report below: worktree/diff inspected, `git diff --check` passed, and critical auth/storage/contribution files were reopened. No implementation was reverted.

## 47. Remaining Pre-Disable Blockers

Only the intentionally deferred deterministic composition work remains: Step 02B must replace the eight manual formal composition records with one validated, cache-safe source and prove disabled-provider/Concord behavior. Foundation-only boot must not yet be certified.

## 48. Final Verdict

PASS. All Step 02A business ownership blockers were removed while every optional package remained enabled and functional. Metadata now matches the proven internal dependency graph. Step 02B can proceed to deterministic composition and dependency validation.

PHASE_13_STEP_02A_STATUS:
PASS

MODE:
FOUNDATION_DECOUPLING_NO_DISABLE_NO_UNDO

OPTIONAL_PACKAGES_DISABLED:
0

STUDENT_ENABLED:
YES

EVENT_ENABLED:
YES

LOST_FOUND_ENABLED:
YES

FOUNDATION_TO_STUDENT_BUSINESS_REFERENCES_BEFORE:
11

FOUNDATION_TO_STUDENT_BUSINESS_REFERENCES_AFTER:
0

FOUNDATION_TO_EVENT_BUSINESS_REFERENCES:
0

FOUNDATION_TO_LOST_FOUND_BUSINESS_REFERENCES_BEFORE:
1

FOUNDATION_TO_LOST_FOUND_BUSINESS_REFERENCES_AFTER:
0

WEB_TO_STUDENT_BUSINESS_REFERENCES:
0

ADMIN_TO_STUDENT_BUSINESS_REFERENCES:
0

CORE_TO_STUDENT_BUSINESS_REFERENCES:
0

USER_TO_STUDENT_BUSINESS_REFERENCES:
0

ROOT_AUTH_TO_STUDENT_BUSINESS_REFERENCES:
0

ROOT_BOOTSTRAP_TO_STUDENT_BUSINESS_REFERENCES:
0

ROOT_FILESYSTEM_TO_LOST_FOUND_BUSINESS_REFERENCES:
0

FORMAL_OPTIONAL_COMPOSITION_REFERENCES:
8 records: 3 root PSR-4 mappings, 3 main-provider registrations, 2 Concord module registrations

STUDENT_AUTH_CONFIGURATION_OWNER:
Webkul\Student via Student/src/Config/auth.php and StudentServiceProvider

STUDENT_GUEST_REDIRECT_OWNER:
Webkul\Student rule contributed to Webkul\Core generic AuthenticationRedirectResolver

STUDENT_MEGA_SEARCH_OWNER:
Webkul\Student via StudentServiceProvider and Student-owned result fragments

STUDENT_QUICK_CREATE_OWNER:
Webkul\Student via Student-owned quick-creation fragment and Admin generic render hook

STUDENT_TRANSLATION_OWNER:
Webkul\Student

LOST_FOUND_PRIVATE_DISK_OWNER:
Webkul\LostAndFound via package config merged by LostAndFoundServiceProvider

LOST_FOUND_STORAGE_PATH_CHANGED:
NO

LOST_FOUND_EXISTING_DATA_MODIFIED:
NO

DATAGRID_MANIFEST_PRESENT:
YES

PACKAGE_METADATA_DEPENDENCIES_MATCH_SOURCE:
PASS

OPTIONAL_DEPENDENCY_GRAPH:
Event -> Student; LostAndFound -> Student; Student -> none; Event and LostAndFound are mutually independent

ROOT_OWNER:
Webkul\Web

ROOT_ROUTE_NAME:
web.home

ROOT_REGRESSION:
PASS

STUDENT_AUTH_REGRESSION:
PASS: Student/university 25 tests (185 assertions); RuntimeSafety 19 tests (46 assertions)

STUDENT_ADMIN_REGRESSION:
PASS: Student ownership/routes/Admin contributions covered in focused and full suites

EVENT_REGRESSION:
PASS: 28 tests (312 assertions)

LOST_FOUND_REGRESSION:
PASS: 275 tests (1570 assertions)

WEB_REGRESSION:
PASS: 47 tests (533 assertions)

THEME_BASE_REGRESSION:
PASS: 49 tests (378 assertions)

LOCALIZATION_REGRESSION:
PASS: Core/Admin locale 24 tests (206 assertions), plus Student/Event/Web locale coverage

FULL_TEST_SUITE:
530 passed (3437 assertions), 18.66s

ROUTE_COUNT:
120

SCHEDULED_TASK_COUNT:
0

COMPOSER_VALIDATE:
PASS

NEW_MIGRATIONS:
0

PRODUCTION_SCHEMA_MODIFIED:
NO

RUNTIME_DATABASE_MODIFIED:
NO

NEW_EXTERNAL_COMPOSER_DEPENDENCIES:
0

NEW_EXTERNAL_NPM_DEPENDENCIES:
0

DESTRUCTIVE_GIT_COMMANDS_USED:
NO

UNDO_OR_REVERT_USED:
NO

FINAL_GIT_DIFF_CHECK:
PASS

TESTED_IMPLEMENTATION_STILL_PRESENT_AFTER_REPORT:
YES

REMAINING_PRE_DISABLE_BLOCKERS:
Step 02B deterministic optional-package composition, dependency/reverse-dependency validation, cache handling, and Foundation-only boot proof

READY_FOR_PHASE_13_STEP_02B:
YES
