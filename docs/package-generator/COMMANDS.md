# Laraseed Package Generator V1 — Command Inventory

This document provides a comprehensive reference for all 16 Artisan commands under the `laraseed` namespace.

---

## Category 1: Base Package Generators

### 1. `laraseed:make-package`
- **Purpose**: Creates a new isolated Laraseed package root.
- **Arguments**:
  - `package` *(required)*: Package identity in `Vendor/PackageName` format (e.g. `Acme/Blog`).
- **Options**:
  - `--plain`: Generate minimal package structure without sample routes/configs.
  - `--force`: Force overwrite of existing package files.
  - `--dry-run`: Simulate generation without modifying filesystem.
- **Generated Path**: `packages/{Vendor}/{PackageName}/`
- **Dependencies**: None.
- **Side Effects**: Creates package directory, `composer.json` (with `extra.laraseed` contract), base ServiceProvider, Concord ModuleServiceProvider, configs, translations, routes, and test skeletons.
- **Example**:
  ```bash
  php artisan laraseed:make-package Acme/Blog
  ```

---

### 2. `laraseed:make-model`
- **Purpose**: Generates an Eloquent model within the package.
- **Arguments**:
  - `package` *(required)*: Package identity (`Vendor/PackageName`).
  - `name` *(required)*: Model class name in StudlyCase (e.g. `Post`).
- **Options**:
  - `--force`: Force overwrite of existing file.
  - `--dry-run`: Simulate generation without writing to disk.
- **Generated Path**: `packages/{Vendor}/{PackageName}/src/Models/{ModelName}.php`
- **Dependencies**: `Illuminate\Database\Eloquent\Model`.
- **Side Effects**: None outside package root.
- **Example**:
  ```bash
  php artisan laraseed:make-model Acme/Blog Post
  ```

---

### 3. `laraseed:make-contract`
- **Purpose**: Generates a Contract interface within the package.
- **Arguments**:
  - `package` *(required)*: Package identity (`Vendor/PackageName`).
  - `name` *(required)*: Contract interface name (e.g. `PostContract`).
- **Options**:
  - `--force`: Force overwrite of existing file.
  - `--dry-run`: Simulate generation without writing to disk.
- **Generated Path**: `packages/{Vendor}/{PackageName}/src/Contracts/{ContractName}.php`
- **Dependencies**: None.
- **Side Effects**: None outside package root.
- **Example**:
  ```bash
  php artisan laraseed:make-contract Acme/Blog PostContract
  ```

---

### 4. `laraseed:make-migration`
- **Purpose**: Generates a timestamped database migration within the package.
- **Arguments**:
  - `package` *(required)*: Package identity (`Vendor/PackageName`).
  - `name` *(required)*: Migration name (e.g. `create_posts_table` or `add_status_to_posts_table`).
- **Options**:
  - `--create=`: Table name to create (auto-inferred if name starts with `create_`).
  - `--table=`: Table name to alter.
  - `--force`: Force overwrite if collision occurs.
  - `--dry-run`: Simulate generation without writing to disk.
- **Generated Path**: `packages/{Vendor}/{PackageName}/src/Database/Migrations/{YYYY_MM_DD_HHMMSS}_{name}.php`
- **Dependencies**: `Illuminate\Database\Migrations\Migration`, `Illuminate\Support\Facades\Schema`.
- **Side Effects**: None on runtime database (does NOT auto-run migrations).
- **Example**:
  ```bash
  php artisan laraseed:make-migration Acme/Blog create_posts_table
  ```

---

### 5. `laraseed:make-repository`
- **Purpose**: Generates a Repository class with optional model binding.
- **Arguments**:
  - `package` *(required)*: Package identity (`Vendor/PackageName`).
  - `name` *(required)*: Repository class name (e.g. `PostRepository`).
- **Options**:
  - `--model=`: Model name to associate with repository (e.g. `Post`). Validates model existence.
  - `--force`: Force overwrite of existing file.
  - `--dry-run`: Simulate generation without writing to disk.
- **Generated Path**: `packages/{Vendor}/{PackageName}/src/Repositories/{RepositoryName}.php`
- **Dependencies**: `Webkul\Core\Eloquent\Repository`.
- **Side Effects**: None outside package root.
- **Example**:
  ```bash
  php artisan laraseed:make-repository Acme/Blog PostRepository --model=Post
  ```

---

### 6. `laraseed:make-request`
- **Purpose**: Generates a FormRequest validation class.
- **Arguments**:
  - `package` *(required)*: Package identity (`Vendor/PackageName`).
  - `name` *(required)*: Request class name (e.g. `StorePostRequest`).
- **Options**:
  - `--force`: Force overwrite of existing file.
  - `--dry-run`: Simulate generation without writing to disk.
- **Generated Path**: `packages/{Vendor}/{PackageName}/src/Http/Requests/{RequestName}.php`
- **Dependencies**: `Illuminate\Foundation\Http\FormRequest`.
- **Side Effects**: None outside package root.
- **Example**:
  ```bash
  php artisan laraseed:make-request Acme/Blog StorePostRequest
  ```

---

### 7. `laraseed:make-controller`
- **Purpose**: Generates a presentation-neutral or API controller.
- **Arguments**:
  - `package` *(required)*: Package identity (`Vendor/PackageName`).
  - `name` *(required)*: Controller class name (e.g. `PostController`).
- **Options**:
  - `--api`: Generate API-tailored controller methods returning JSON responses.
  - `--force`: Force overwrite of existing file.
  - `--dry-run`: Simulate generation without writing to disk.
- **Generated Path**: `packages/{Vendor}/{PackageName}/src/Http/Controllers/{ControllerName}.php`
- **Dependencies**: `App\Http\Controllers\Controller` / `Illuminate\Routing\Controller`.
- **Side Effects**: None outside package root.
- **Example**:
  ```bash
  php artisan laraseed:make-controller Acme/Blog PostController --api
  ```

---

### 8. `laraseed:make-route`
- **Purpose**: Generates a package-owned route file.
- **Arguments**:
  - `package` *(required)*: Package identity (`Vendor/PackageName`).
  - `name` *(required)*: Route file name without extension (e.g. `web` or `api`).
- **Options**:
  - `--type=`: Route type (`web` or `api`, defaults to `web`).
  - `--force`: Force overwrite of existing file.
  - `--dry-run`: Simulate generation without writing to disk.
- **Generated Path**: `packages/{Vendor}/{PackageName}/src/Routes/{name}.php`
- **Dependencies**: `Illuminate\Support\Facades\Route`.
- **Side Effects**: None outside package root (does NOT modify root `routes/*`).
- **Example**:
  ```bash
  php artisan laraseed:make-route Acme/Blog custom_api --type=api
  ```

---

### 9. `laraseed:make-provider`
- **Purpose**: Generates an additional ServiceProvider within the package.
- **Arguments**:
  - `package` *(required)*: Package identity (`Vendor/PackageName`).
  - `name` *(required)*: Provider class name (e.g. `EventServiceProvider`).
- **Options**:
  - `--force`: Force overwrite of existing file.
  - `--dry-run`: Simulate generation without writing to disk.
- **Generated Path**: `packages/{Vendor}/{PackageName}/src/Providers/{ProviderName}.php`
- **Dependencies**: `Illuminate\Support\ServiceProvider`.
- **Side Effects**: None outside package root.
- **Example**:
  ```bash
  php artisan laraseed:make-provider Acme/Blog EventServiceProvider
  ```

---

### 10. `laraseed:make-module-provider`
- **Purpose**: Generates or regenerates Concord `ModuleServiceProvider`.
- **Arguments**:
  - `package` *(required)*: Package identity (`Vendor/PackageName`).
- **Options**:
  - `--force`: Force overwrite of existing file.
  - `--dry-run`: Simulate generation without writing to disk.
- **Generated Path**: `packages/{Vendor}/{PackageName}/src/Providers/ModuleServiceProvider.php`
- **Dependencies**: `Konekt\Concord\BaseModuleServiceProvider`.
- **Side Effects**: None outside package root.
- **Example**:
  ```bash
  php artisan laraseed:make-module-provider Acme/Blog
  ```

---

### 11. `laraseed:make-event`
- **Purpose**: Generates an Event class.
- **Arguments**:
  - `package` *(required)*: Package identity (`Vendor/PackageName`).
  - `name` *(required)*: Event class name (e.g. `PostPublished`).
- **Options**:
  - `--force`: Force overwrite of existing file.
  - `--dry-run`: Simulate generation without writing to disk.
- **Generated Path**: `packages/{Vendor}/{PackageName}/src/Events/{EventName}.php`
- **Dependencies**: `Illuminate\Foundation\Events\Dispatchable`, `Illuminate\Queue\SerializesModels`.
- **Side Effects**: None outside package root.
- **Example**:
  ```bash
  php artisan laraseed:make-event Acme/Blog PostPublished
  ```

---

### 12. `laraseed:make-listener`
- **Purpose**: Generates an Event Listener class with optional event type-hinting.
- **Arguments**:
  - `package` *(required)*: Package identity (`Vendor/PackageName`).
  - `name` *(required)*: Listener class name (e.g. `SendPostNotification`).
- **Options**:
  - `--event=`: Target event class name. Validates physical existence before generating.
  - `--force`: Force overwrite of existing file.
  - `--dry-run`: Simulate generation without writing to disk.
- **Generated Path**: `packages/{Vendor}/{PackageName}/src/Listeners/{ListenerName}.php`
- **Dependencies**: Target event class (if `--event` supplied).
- **Side Effects**: None outside package root.
- **Example**:
  ```bash
  php artisan laraseed:make-listener Acme/Blog SendPostNotification --event=PostPublished
  ```

---

### 13. `laraseed:make-command`
- **Purpose**: Generates an Artisan console command.
- **Arguments**:
  - `package` *(required)*: Package identity (`Vendor/PackageName`).
  - `name` *(required)*: Command class name (e.g. `PrunePostsCommand`).
- **Options**:
  - `--signature=`: Custom command signature (e.g. `blog:prune`). Rejects reserved `laraseed:` namespace prefix.
  - `--force`: Force overwrite of existing file.
  - `--dry-run`: Simulate generation without writing to disk.
- **Generated Path**: `packages/{Vendor}/{PackageName}/src/Console/Commands/{CommandName}.php`
- **Dependencies**: `Illuminate\Console\Command`.
- **Side Effects**: Automatically discovered and registered by the package ServiceProvider when running in console.
- **Example**:
  ```bash
  php artisan laraseed:make-command Acme/Blog PrunePostsCommand --signature=blog:prune
  ```

---

### 14. `laraseed:make-seeder`
- **Purpose**: Generates a Database Seeder class.
- **Arguments**:
  - `package` *(required)*: Package identity (`Vendor/PackageName`).
  - `name` *(required)*: Seeder class name (e.g. `PostSeeder`).
- **Options**:
  - `--force`: Force overwrite of existing file.
  - `--dry-run`: Simulate generation without writing to disk.
- **Generated Path**: `packages/{Vendor}/{PackageName}/src/Database/Seeders/{SeederName}.php`
- **Dependencies**: `Illuminate\Database\Seeder`.
- **Side Effects**: None outside package root.
- **Example**:
  ```bash
  php artisan laraseed:make-seeder Acme/Blog PostSeeder
  ```

---

## Category 2: Optional Admin & DataGrid Generators

### 15. `laraseed:make-datagrid`
- **Purpose**: Generates a DataGrid class explicitly extending `Webkul\DataGrid\DataGrid`.
- **Arguments**:
  - `package` *(required)*: Package identity (`Vendor/PackageName`).
  - `name` *(required)*: DataGrid class name (e.g. `PostDataGrid`).
- **Options**:
  - `--model=`: Model name to bind to DataGrid query builder. Validates physical existence.
  - `--force`: Force overwrite of existing file.
  - `--dry-run`: Simulate generation without writing to disk.
- **Generated Path**: `packages/{Vendor}/{PackageName}/src/Admin/DataGrids/{DataGridName}.php` (if Admin layer present) or `packages/{Vendor}/{PackageName}/src/DataGrids/{DataGridName}.php`.
- **Dependencies**: `Webkul\DataGrid\DataGrid`, `Illuminate\Database\Query\Builder`, `Illuminate\Support\Facades\DB`.
- **Side Effects**: Declares explicit usage of DataGrid.
- **Example**:
  ```bash
  php artisan laraseed:make-datagrid Acme/Blog PostDataGrid --model=Post
  ```

---

### 16. `laraseed:make-admin`
- **Purpose**: Generates the optional Admin integration layer skeleton atomically.
- **Arguments**:
  - `package` *(required)*: Package identity (`Vendor/PackageName`).
- **Options**:
  - `--force`: Force overwrite of existing files.
  - `--dry-run`: Simulate generation without writing to disk.
- **Generated Path**: `packages/{Vendor}/{PackageName}/src/Admin/`
  - `src/Admin/Providers/AdminServiceProvider.php`
  - `src/Admin/Config/menu.php`
  - `src/Admin/Config/acl.php`
  - `src/Admin/Http/Controllers/AdminController.php`
  - `src/Admin/Routes/web.php`
  - `src/Admin/Resources/lang/en/app.php`
  - `src/Admin/Resources/lang/ar/app.php`
  - `src/Admin/Resources/views/index.blade.php`
- **Dependencies**: `Webkul\Admin`, standard Admin components (`<x-admin::layouts>`).
- **Side Effects**: Package ServiceProvider automatically registers `AdminServiceProvider` when present.
- **Example**:
  ```bash
  php artisan laraseed:make-admin Acme/Blog
  ```

---

## Category 3: Laraseed Diagnostics & Status Commands

### 17. `laraseed:packages`
- **Purpose**: Displays the catalog, installed, enabled, and dependency state of all Laraseed optional packages. Read-only.
- **Example**:
  ```bash
  php artisan laraseed:packages
  ```

### 18. `laraseed:version`
- **Purpose**: Displays the current installed version of Laraseed.
- **Example**:
  ```bash
  php artisan laraseed:version
  ```
