# Laraseed Package Generator V1

> **Status**: STABLE  
> **Certification**: PASS  
> **Foundation Runtime Coupling**: NONE  

`laraseed/package-generator` is the official development tool for generating modular, presentation-neutral, and optional Admin-integrated packages within the **Laraseed Modular Application Foundation**.

---

## Key Principles

1. **Development-Only Dependency**: The generator operates strictly in `require-dev` as a development CLI tool. The Laraseed Foundation runtime has **zero dependency** on the generator.
2. **Absolute Foundation Isolation**: Creating, updating, or deleting packages does not mutate root application files, `bootstrap/providers.php`, `config/laraseed.php`, or root routes.
3. **Presentation-Neutral by Default**: `laraseed:make-package` generates clean domain/business packages without Admin or DataGrid dependencies.
4. **Opt-In Admin & DataGrid Integration**: Admin integration (`laraseed:make-admin`) and DataGrids (`laraseed:make-datagrid`) are explicitly generated only when needed.
5. **Deterministic Composition**: Optional packages are declared via `extra.laraseed` in `composer.json` and enabled strictly through `LARASEED_OPTIONAL_PACKAGES` via `OptionalPackageComposition`.
6. **Zero-Residue Removal**: Physically removing a package directory removes all its routes, configs, menu, ACL, and commands cleanly with no orphaned references.

---

## Quickstart

### 1. Generate a New Package
```bash
php artisan laraseed:make-package Acme/Blog
```

### 2. Generate Core Components
```bash
# Model & Contract
php artisan laraseed:make-model Acme/Blog Post
php artisan laraseed:make-contract Acme/Blog PostContract

# Database Migration & Seeder
php artisan laraseed:make-migration Acme/Blog create_posts_table
php artisan laraseed:make-seeder Acme/Blog PostSeeder

# Repository, Request & Controller
php artisan laraseed:make-repository Acme/Blog PostRepository --model=Post
php artisan laraseed:make-request Acme/Blog StorePostRequest
php artisan laraseed:make-controller Acme/Blog PostController
```

### 3. Generate Optional Admin Layer
```bash
# Generate Admin skeleton (controllers, routes, views, menu, acl, translations)
php artisan laraseed:make-admin Acme/Blog

# Generate Admin DataGrid
php artisan laraseed:make-datagrid Acme/Blog PostDataGrid --model=Post
```

### 4. Enable Package in Runtime
In your `.env`:
```env
LARASEED_OPTIONAL_PACKAGES=blog
```

Verify installed packages:
```bash
php artisan laraseed:packages
```

---

## Documentation Index

- [Commands Reference](COMMANDS.md) — Comprehensive reference for all 16 Artisan commands.
- [Architecture & Invariants](ARCHITECTURE.md) — Architectural boundaries, lifecycle, and V2 roadmap.
