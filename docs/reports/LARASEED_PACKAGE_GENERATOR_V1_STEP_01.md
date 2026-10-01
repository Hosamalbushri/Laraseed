# Laraseed Package Generator V1 — Step 01 Report

**تاريخ التنفيذ:** 2026-10-01  
**حالة التنفيذ:** مكتمل ومتحقق بالكامل بنسبة 100%  
**الهدف:** بناء المولد المستقل `laraseed:make-package` لتوليد حزم Laraseed الاختيارية (Optional Packages) المتوافقة مع معمارية البذرة وعقود `OptionalPackageManifestLoader`.

---

## 1. Generator Location & Architecture

تم تصميم المولد كـ **Composer Development Package مستقل** بالكامل وليس جزءًا من `Webkul\Core` أو Foundation runtime:

- **مسار الحزمة:** `packages/Laraseed/PackageGenerator`
- **الاسم الفني لحزمة Composer:** `laraseed/package-generator` (مسجلة في `require-dev`)
- **PHP Namespace:** `Laraseed\PackageGenerator`
- **Service Provider:** `Laraseed\PackageGenerator\Providers\PackageGeneratorServiceProvider`
- **التسجيل والاكتشاف:** يتم اكتشاف الحزمة تلقائيًا عبر Laravel Package Discovery (`extra.laravel.providers`) عند التطور فقط.
- **استقلالية الـ Foundation:** Foundation runtime لا تعتمد على هذه الحزمة بأي شكل من الأشكال.

---

## 2. Internal Structure & Responsibilities

تم تقسيم كود المولد إلى طبقات واضحة ومحددة المسؤولية بدون overengineering:

```text
packages/Laraseed/PackageGenerator/
├── composer.json
├── src/
│   ├── Console/
│   │   └── Commands/
│   │       └── PackageMakeCommand.php
│   ├── Exceptions/
│   │   └── PackageGenerationException.php
│   ├── Generators/
│   │   ├── FilesystemWriter.php
│   │   ├── GenerationPlan.php
│   │   ├── PackageGenerator.php
│   │   └── StubRenderer.php
│   ├── Providers/
│   │   └── PackageGeneratorServiceProvider.php
│   └── Support/
│       ├── PackageIdentity.php
│       └── PackageNameValidator.php
└── stubs/
    ├── composer.json.stub
    ├── config.php.stub
    ├── lang_ar.php.stub
    ├── lang_en.php.stub
    ├── module_provider.php.stub
    ├── package_test.php.stub
    ├── provider.php.stub
    ├── routes_api.php.stub
    ├── routes_web.php.stub
    └── test_case.php.stub
```

---

## 3. Registered Command

تم تسجيل الأمر الخاص بالمولد تحت الاسم الفني:

```bash
php artisan laraseed:make-package Vendor/PackageName {--dry-run} {--force}
```

- `Vendor/PackageName`: اسم الحزمة بتنسيق `Vendor/PackageName` (مثل `Acme/BlogEngine`).
- `--dry-run`: يعرض شجرة وسلسلة الملفات المقترحة دون إجراء أي كتابة على قرص الملفات.
- `--force`: يسمح بإعادة التوليد والكتابة فوق الملفات المملوكة لنفس وصفة التوليد (Recipe).

---

## 4. Generated Package Structure & Composition Compatibility

تولّد الحزمة البنية القياسية القياسية القياسية حسب قواعد `06_*`, `08_*`, `09_*`, `11_*`:

```text
packages/Vendor/PackageName/
├── composer.json
├── src/
│   ├── Config/
│   │   └── package_id.php
│   ├── Providers/
│   │   ├── PackageNameServiceProvider.php
│   │   └── ModuleServiceProvider.php
│   ├── Resources/
│   │   └── lang/
│   │       ├── ar/
│   │       │   └── app.php
│   │       └── en/
│   │           └── app.php
│   └── Routes/
│       ├── api.php
│       └── web.php
└── tests/
    ├── TestCase.php
    └── Feature/
        └── PackageTest.php
```

### توافق عقد `extra.laraseed`

ينتج المولد ملف `composer.json` صالحًا يلبي عقد `OptionalPackageManifestLoader` بالضبط:

```json
{
    "name": "vendor/package-name",
    "description": "Laraseed PackageName Optional Package",
    "type": "library",
    "license": "MIT",
    "autoload": {
        "psr-4": {
            "Vendor\\PackageName\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Vendor\\PackageName\\Tests\\": "tests/"
        }
    },
    "extra": {
        "laraseed": {
            "id": "package_name",
            "type": "optional",
            "provider": "Vendor\\PackageName\\Providers\\PackageNameServiceProvider",
            "concord_module": "Vendor\\PackageName\\Providers\\ModuleServiceProvider"
        }
    }
}
```

---

## 5. Safety, Validation & Collision Rules

1. **التحقق من المدخلات (Safety Validation):**
   - يرفض المدخلات الفارغة، المسارات المطلقة (Absolute paths)، ومحاولات الـ Path Traversal (`..`, `\`, null bytes).
   - يرفض الأسماء التي لا تطابق صيغة `Vendor/PackageName`.
   - يرفض أسماء الـ PHP Namespaces والـ Composer packages غير الصالحة.
   - يرفض أسماء الـ Foundation المحجوزة (`Core`, `Admin`, `User`, `DataGrid`, `Installer`, `DebugBar`, `Laraseed`).
2. **استراتيجية التضارب (Preflight Collision Check):**
   - يقوم المولد بعمل فحص مسبق (Preflight check) لجميع المسارات المستهدفة قبل كتابة أي بت على القرص.
   - في حال وجود ملف واحد متضارب وبدون تفعيل خيار `--force`، يتم إيقاف العملية فورًا ومنع التوليد الجزئي (Zero Partial Generation).
3. **سلوك `--dry-run`:**
   - يعرض جدولا بجميع الملفات التي سيتم إنشاؤها وحجمها وإجراء التوليد، مع التأكيد بعدم كتابة أي ملف.
4. **سلوك `--force`:**
   - لا يسمح بحذف مجلدات كاملة، بل يسمح فقط برفع الاستبدال المباشر للملفات المحددة في وصفة التوليد.

---

## 6. Compatibility Proof with OptionalPackageManifestLoader

تم إثبات التوافق الفعلي برمجياً عبر اختبار الوحدة:
- قراءة مانيفست `composer.json` المولد بواسطة `OptionalPackageManifestLoader->load([$path])`.
- التحقق من إرجاع مصفوفة الحزم مع معرف الحزمة القياسي `package_id` والتأكد من ملاءمة Provider و Module classes.

---

## 7. Tests Added

تمت إضافة مجموعة اختبارات شاملة في:
`tests/Feature/Laraseed/PackageGeneratorTest.php`

وتغطي:
- `test_valid_package_generation_creates_all_expected_files`
- `test_generated_composer_json_schema_and_extra_laraseed_contract`
- `test_generated_package_is_compatible_with_optional_package_manifest_loader`
- `test_generated_namespace_and_classes_have_correct_syntax`
- `test_invalid_package_names_are_rejected`
- `test_invalid_package_names_command_returns_failure`
- `test_path_traversal_attempts_are_rejected`
- `test_reserved_foundation_names_are_rejected`
- `test_collision_without_force_flag_fails_and_aborts`
- `test_dry_run_mode_creates_no_files_on_disk`
- `test_force_flag_allows_overwriting_recipe_files`
- `test_no_partial_generation_after_failed_preflight`

---

## 8. Full Verification Results

| الأمر | النتيجة |
| --- | --- |
| `composer validate --strict` | **PASS** (Valid) |
| `composer validate --strict packages/Laraseed/PackageGenerator/composer.json` | **PASS** (Valid) |
| `php artisan list laraseed` | **PASS** (أمر `laraseed:make-package` مسجل ومتاح) |
| `php artisan laraseed:packages` | **PASS** (Foundation active state preserved) |
| `php artisan route:list` | **PASS** (67 routes maintained) |
| `./vendor/bin/pest` | **PASS** (139 tests passed / 1423 assertions) |
| `git diff --check` | **PASS** (Return code 0) |
| `git status --short` | **Clean worktree except expected new files & composer config** |

---
