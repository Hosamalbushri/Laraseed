# Laraseed Package Generator V1 — Architecture & Invariants

This document outlines the architectural boundaries, contracts, invariants, and technical debt of the Laraseed Package Generator.

---

## 1. Architectural Invariants

1. **Development-Only Dependency**: The generator is registered strictly under `require-dev` as `laraseed/package-generator`. Foundation runtime has zero coupling to the generator.
2. **Direction of Dependency**:
   - `Foundation → Optional Package` : **FORBIDDEN**
   - `Optional Package → Foundation public contracts` : **ALLOWED**
   - `Optional Admin Integration → Admin/DataGrid` : **EXPLICIT & OPT-IN**
   - `Admin → Specific Optional Package` : **FORBIDDEN**
   - `Generator → Foundation Runtime` : **DEV ONLY**
3. **No Foundation File Mutation**: Running generator commands never mutates:
   - `bootstrap/providers.php`
   - `config/laraseed.php`
   - `config/concord.php`
   - root `routes/*`
   - `packages/Webkul/*`
4. **Deterministic Composition**: Optional packages are loaded through `OptionalPackageManifestLoader` and governed by `LARASEED_OPTIONAL_PACKAGES` via `OptionalPackageComposition`.
5. **No Automatic Database Execution**: Migrations are generated into `src/Database/Migrations/` but are **never** executed automatically during package creation.
6. **Zero Residue on Removal**: Deleting `packages/Vendor/PackageName` leaves zero dangling imports or side effects in the Foundation.

---

## 2. Package Manifest Contract (`extra.laraseed`)

Every generated package contains a `composer.json` with the strict `extra.laraseed` schema:

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

### Schema Rules:
- `id`: Lowercase alphanumeric snake_case identifier matching `/^[a-z][a-z0-9_]*$/`.
- `type`: Must be `"optional"`.
- `provider`: FQN of class extending `Illuminate\Support\ServiceProvider`.
- `concord_module`: FQN of class extending `Konekt\Concord\BaseModuleServiceProvider`.

---

## 3. Package Lifecycle & Boot Sequence

```text
[ .env: LARASEED_OPTIONAL_PACKAGES=blog ]
                    ↓
[ config/laraseed.php: OptionalPackageComposition ]
                    ↓
[ App Bootstrap: Boot Optional ServiceProviders ]
                    ↓
[ BlogServiceProvider::register() ]
    ├── Merges package config: config('blog')
    └── Checks if class_exists(AdminServiceProvider::class)
            └── Registers Blog\Admin\Providers\AdminServiceProvider (if present)
                    ├── Merges menu.admin
                    └── Merges acl
                    ↓
[ BlogServiceProvider::boot() ]
    ├── Loads translations: loadTranslationsFrom(...)
    ├── Loads views: loadViewsFrom(...)
    ├── Loads routes: loadRoutesFrom(web.php, api.php)
    ├── Loads migrations: loadMigrationsFrom(...)
    ├── Auto-discovers Console Commands in src/Console/Commands/*.php
    └── Boots AdminServiceProvider:
            ├── Registers admin routes (prefix: admin_path, middleware: web, user)
            ├── Loads admin views (namespace: blog_admin)
            └── Loads admin translations (namespace: blog_admin)
```

---

## 4. Security Baseline

- **Path Containment**: All generator commands enforce that target files resolve strictly within the package directory root. Path traversal sequences (`../`, absolute paths, symlink escapes) are rejected during preflight.
- **Identifier Validation**: Class names, namespaces, migration names, and signatures are strictly validated against PHP identifier conventions.
- **Reserved Namespaces**: Namespaces starting with `Webkul`, `Laraseed`, `Illuminate`, or `App` are rejected to prevent namespace collisions with core subsystems.
- **Atomic Multi-File Execution**: Multi-file plans (`make-package`, `make-admin`) run preflight collision checks on all planned files. If any collision occurs without `--force`, zero files are written.
- **Safe Dry-Run Mode**: When `--dry-run` is active, no files or directories are created on disk.

---

## 5. Technical Debt & V2 Roadmap

### `V2-ARCH-001`: Declarative Admin Integration Manifest Discovery
- **Current V1 Implementation**: Base `ServiceProvider` checks `class_exists(\Vendor\PackageName\Admin\Providers\AdminServiceProvider::class)` dynamically at runtime to boot the Admin integration layer.
- **V2 Objective**: Review and evaluate replacing `class_exists` runtime probe with a declarative metadata field (e.g. `extra.laraseed.admin_provider` or explicit manifest composition) to achieve 100% static declarative discovery without coupling Foundation runtime.
