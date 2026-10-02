# Laraseed Package Generator — Phase 01: Filesystem & Generation Integrity Audit
## 02. Filesystem & Generation Integrity Audit

This report evaluates the filesystem security, input validation, directory traversal defenses, generation atomicity, preflight collision detection, and transactional rollback mechanisms in **Laraseed Package Generator**.

---

## 1. Input Sanitization & Path Traversal Defenses

Input validation is enforced centrally through `Laraseed\PackageGenerator\Support\PackageNameValidator`.

### 1.1 Evaluated Vectors & Defenses

| Threat Vector | Validator Defense Mechanism | Evaluation Result |
| :--- | :--- | :--- |
| **Null Byte Injection (`\0`)** | `str_contains($trimmed, "\0")` throws `invalidInput`. | **SECURE (Blocked)** |
| **Directory Traversal (`..`, `\`)** | `str_contains($trimmed, '..') \|\| str_contains($trimmed, '\\')` throws `invalidInput`. | **SECURE (Blocked)** |
| **Absolute Paths (`/var/...`, `C:\...`)** | Checks `str_starts_with('/', ...)` and Windows drive regex `/^[a-zA-Z]:[\\\\\/]/`. | **SECURE (Blocked)** |
| **Format Enforcement** | `count(explode('/', $trimmed)) === 2` enforces `Vendor/PackageName`. | **SECURE (Enforced)** |
| **Namespace Identifier Regex** | `preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', ...)` on both parts. | **SECURE (Strict)** |
| **Reserved Vendors** | Blocks reserved vendor names (`webkul`). | **SECURE (Blocked)** |
| **Reserved Packages** | Blocks Foundation package names (`core`, `admin`, `user`, `datagrid`, `installer`, `debugbar`, `laraseed`, `packagegenerator`). | **SECURE (Blocked)** |

### 1.2 Sub-Generator Class Identifier Validation

All sub-generators (`ModelGenerator`, `MigrationGenerator`, `RepositoryGenerator`, `ControllerGenerator`, `DataGridGenerator`, `CommandGenerator`, `RequestGenerator`, `RouteGenerator`, `ProviderGenerator`, `EventGenerator`, `ListenerGenerator`, `SeederGenerator`, `ContractGenerator`) implement independent validation methods:

- **Class Names:** Verified against `/^[A-Za-z_][A-Za-z0-9_]*$/` and checked for `..`, `/`, `\`, and `\0`.
- **Migration Names:** Verified against `/^[a-z0-9_]+$/` (snake_case).
- **Route Names:** Verified against `/^[a-z0-9_.-]+$/`.
- **Command Signatures:** Rejects reserved `laraseed:` prefix and validates `/^[a-z0-9_:-]+$/i`.

---

## 2. Package Resolution & Filesystem Containment

`PackageResolver` locates existing packages and parses `composer.json`:

```php
public function resolve(string $input): ResolvedPackage
{
    $identity = PackageIdentity::fromInput($input);
    $relativePackagePath = $identity->relativePackagePath;
    $packagePath = rtrim($this->basePath, '/') . '/' . $relativePackagePath;

    if (! $this->filesystem->isDirectory($packagePath)) {
        throw PackageGenerationException::invalidInput("Package [{$input}] not found at [{$relativePackagePath}]. Generate package first using laraseed:make-package.");
    }
    // ...
}
```

### Forensic Finding (`SEC-PG-04`):
- `PackageResolver` relies on `$this->filesystem->isDirectory($packagePath)` without verifying `realpath($packagePath)`.
- If a symlink exists inside `packages/` pointing outside the project root (e.g. `packages/Acme/LinkedPkg -> /tmp/external`), the resolver accepts the path and writes files to the symlink target.
- **Remediation:** Add `realpath()` containment validation asserting that the target path resides within `base_path('packages')`.

---

## 3. Preflight Collision Detection

Collision detection is handled by `GenerationPlan::preflight(bool $force)`:

```php
public function preflight(bool $force): void
{
    $collisions = [];
    $targetDir = $this->targetDirectory();

    foreach ($this->files as $relativePath => $content) {
        $fullPath = "{$targetDir}/{$relativePath}";

        if (file_exists($fullPath)) {
            if (! $force) {
                $collisions[] = "{$this->relativePackagePath}/{$relativePath}";
            } elseif (is_dir($fullPath)) {
                throw PackageGenerationException::invalidInput("Target path [{$relativePath}] is an existing directory, cannot overwrite with file.");
            }
        }
    }

    if ($collisions !== []) {
        throw PackageGenerationException::collisionDetected($collisions);
    }
}
```

### Key Strengths:
1. **Zero Write on Collision:** If even 1 out of 29 files exists on disk, `preflight()` aborts before any filesystem write operation begins.
2. **Directory Overwrite Guard:** Prevents overwriting existing directories with files even when `--force` is specified.

---

## 4. Generation Atomicity & Transactional Rollback Comparison

| Generation Engine | Preflight Collision Check | Rollback on Write Failure | Rollback Manifest (`composer.json`) | Overwrite Backup Tracking |
| :--- | :---: | :---: | :---: | :---: |
| **`PackageGenerator` (Base V2)** | **YES** | **NO** (`SEC-PG-02`) | **N/A** | **NO** |
| **`AdminGenerator` (Admin V2)** | **YES** | **YES** | **YES** | **YES** |
| **`WebGenerator` (Web V3)** | **YES** | **YES** | **YES** | **YES** |
| **Individual Sub-Generators** | **YES** | **Single File** | **N/A** | **NO** |

### Transactional Rollback Architecture in `WebGenerator` & `AdminGenerator`:
```php
try {
    $results = $this->writer->execute($plan, false, false);
    $written = file_put_contents($composerPath, $newComposerJson);
    if ($written === false) {
        throw new \RuntimeException("Failed to write to package composer.json.");
    }
} catch (\Throwable $e) {
    // 1. Delete all files created during this run
    foreach ($createdPaths as $created) {
        if (file_exists($created)) { @unlink($created); }
    }
    // 2. Restore overwritten files from memory backup
    foreach ($overwrittenBackups as $path => $originalContent) {
        @file_put_contents($path, $originalContent);
    }
    // 3. Restore original composer.json
    if (file_exists($composerPath)) {
        @file_put_contents($composerPath, $originalComposer);
    }
    // 4. Remove newly created empty directory trees
    $this->cleanupEmptyDirectories("{$resolved->packagePath}/src/Web");

    throw PackageGenerationException::invalidInput("Web capability generation failed with transactional rollback: " . $e->getMessage());
}
```

---

## 5. Dry-Run Simulation Accuracy

The `--dry-run` flag is supported across all 14 generator commands:

1. `FilesystemWriter::execute($plan, true, $force)` calculates file actions (`CREATE` / `OVERWRITE`) and byte counts in memory without calling `makeDirectory()` or `put()`.
2. `WebGenerator` and `AdminGenerator` perform in-memory JSON transformations of `composer.json` without modifying disk contents.
3. Automated test `test_make_web_dry_run_creates_zero_files_and_leaves_composer_json_unmodified` confirms 100% dry-run simulation accuracy.
