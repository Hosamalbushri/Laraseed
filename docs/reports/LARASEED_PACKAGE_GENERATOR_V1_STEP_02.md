# Laraseed Package Generator V1 — Step 02 Report

**تاريخ التنفيذ:** 2026-10-01  
**حالة التنفيذ:** مكتمل ومتحقق بالكامل بنسبة 100%  
**الهدف:** توسيع مولد الحزم `laraseed/package-generator` بتضمين المولدات الأولى للمكونات القياسية: Model و Contract و Migration دون المساس بـ Step 01 أو بـ Core/Foundation runtime.

---

## 1. Commands Added

تمت إضافة ثلاث أوامر جديدة ضمن نطاق `laraseed`:

```bash
php artisan laraseed:make-model Vendor/PackageName ModelName {--dry-run} {--force}
php artisan laraseed:make-contract Vendor/PackageName ContractName {--dry-run} {--force}
php artisan laraseed:make-migration Vendor/PackageName migration_name {--dry-run} {--force}
```

---

## 2. Shared Infrastructure Changes

تمت إعادة استخدام وتطوير البنية التحتية للمولد لتجنب تكرار الكود:

- **[PackageResolver](file:///home/hosam/Documents/CampusHub-main/packages/Laraseed/PackageGenerator/src/Support/PackageResolver.php):** فك وحساب الحزمة المستهدفة من مجلد `packages/Vendor/PackageName` والتحقق من وجود المجلد وملف `composer.json` وعقد `extra.laraseed`.
- **[GenerationPlan](file:///home/hosam/Documents/CampusHub-main/packages/Laraseed/PackageGenerator/src/Generators/GenerationPlan.php):** تم تحسينها لتقبل المسار النسبي للحزمة بدلاً من التقيد بالـ Identity، مما سمح باستخدام نفس آلية فحص التضارب Preflight وحساب المسارات لكل المكونات.
- **[FilesystemWriter](file:///home/hosam/Documents/CampusHub-main/packages/Laraseed/PackageGenerator/src/Generators/FilesystemWriter.php):** تنفيذ مشترك لكتابة الملفات ومحاكاة `--dry-run`.
- **[StubRenderer](file:///home/hosam/Documents/CampusHub-main/packages/Laraseed/PackageGenerator/src/Generators/StubRenderer.php):** معالجة وتقديم القوالب البرمجية المضافة.

---

## 3. Package & Namespace Resolution Mechanism

قبل توليد أي مكون:
1. يتم فحص وجود مجلد الحزمة في `packages/Vendor/PackageName`.
2. يتم قراءة `composer.json` للحزمة والتحقق من وجود `extra.laraseed`.
3. استخراج الـ PSR-4 Namespace من قسم `autoload.psr-4` تلقائيًا (مثل `"Acme\\Blog\\": "src/"` -> `Acme\Blog`).
4. عدم افتراض `Webkul` أو أي اسم ثابت، واستخدام الـ Namespace الحقيقي المعلن في المانيفست.

---

## 4. Generated Paths & Conventions

- **Model:**
  - المسار: `packages/Vendor/PackageName/src/Models/ModelName.php`
  - الـ Namespace: `<PackageNamespace>\Models`
  - الوراثة: `Illuminate\Database\Eloquent\Model`
- **Contract:**
  - المسار: `packages/Vendor/PackageName/src/Contracts/ContractName.php`
  - الـ Namespace: `<PackageNamespace>\Contracts`
  - التنسيق: `interface ContractName`
- **Migration:**
  - المسار: `packages/Vendor/PackageName/src/Database/Migrations/{YYYY_MM_DD_HHMMSS}_{migration_name}.php`
  - النمط:
    - إذا كان اسم الهجرة `create_{table}_table` يتم توليد `Schema::create('{table}', ...)` و `Schema::dropIfExists('{table}')`.
    - إذا كان اسمًا عامًا يتم توليد هيكل `up()` و `down()` خاليين لتعبئتهما.

---

## 5. Safety Guarantees

- **الفحص المسبق للتضارب (Preflight Collision Check):** يتم الفحص الكامل قبل كتابة أي بت على القرص؛ وفي حال وجود تضارب وبدون `--force` يتم إيقاف العملية ومنع الكتابة الجزئية.
- **حظر الاختراق والمسارات الشاذة (Path Traversal & Boundaries):** يرفض المولد تسلسلات `..` و `\` و `/` في أسماء الكلاسات والهجرات، ويتحقق برمجياً من بقاء الملفات المولدة حصرياً داخل جذور مجلد الحزمة (`packages/Vendor/PackageName`).
- **فحص الأسماء الصالحة:** اشتراط أن تكون أسماء الكلاسات معرّفات PHP صالحة (`^[A-Za-z_][A-Za-z0-9_]*$`) وأسماء الهجرات صيغة snake_case صغيرة (`^[a-z0-9_]+$`).
- **دعم `--dry-run` و `--force`:** متوفر بنفس المعايير والأمان الصارم في كافة الأوامر.

---

## 6. Tests Added

تم تحديث وتوسيع سويت الاختبارات في:
[tests/Feature/Laraseed/PackageGeneratorTest.php](file:///home/hosam/Documents/CampusHub-main/tests/Feature/Laraseed/PackageGeneratorTest.php)

الاختبارات الجديدة المضافة (Step 02):
- `test_make_model_generates_model_class_with_correct_namespace`
- `test_make_contract_generates_contract_interface_with_correct_namespace`
- `test_make_migration_generates_migration_file_with_schema`
- `test_model_generation_resolves_custom_psr4_namespace_from_composer_json`
- `test_make_components_fails_when_package_does_not_exist`
- `test_make_components_fails_when_package_manifest_is_malformed`
- `test_make_components_fails_on_invalid_class_names`
- `test_make_components_fails_on_invalid_migration_names`
- `test_make_components_fails_on_path_traversal`
- `test_make_components_dry_run_mode_creates_no_files`
- `test_make_components_collisions_fail_without_force`
- `test_make_components_force_flag_allows_overwriting`
- `test_make_components_remain_strictly_contained_inside_package_root`

---

## 7. Full Verification Results

| الأمر | النتيجة |
| --- | --- |
| `composer validate --strict` | **PASS** (Valid) |
| `composer validate --strict packages/Laraseed/PackageGenerator/composer.json` | **PASS** (Valid) |
| `php artisan list laraseed` | **PASS** (أوامر `make-package`, `make-model`, `make-contract`, `make-migration` مسجلة ومتاحة) |
| `./vendor/bin/pest tests/Feature/Laraseed/PackageGeneratorTest.php` | **PASS** (25 tests passed / 93 assertions) |
| `./vendor/bin/pest` | **PASS** (152 tests passed / 1468 assertions) |
| `php artisan laraseed:packages` | **PASS** (Foundation active state preserved) |
| `php artisan route:list` | **PASS** (67 routes maintained) |
| `git diff --check` | **PASS** (Return code 0) |
| `git status --short` | **Clean worktree except expected new files & composer config** |

---
