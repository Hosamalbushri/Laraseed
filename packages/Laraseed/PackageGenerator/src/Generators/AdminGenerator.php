<?php

namespace Laraseed\PackageGenerator\Generators;

use Illuminate\Support\Str;
use JsonException;
use Laraseed\PackageGenerator\Exceptions\PackageGenerationException;
use Laraseed\PackageGenerator\Support\PackageResolver;
use Laraseed\PackageGenerator\Support\ResolvedPackage;

class AdminGenerator
{
    protected string $basePath;

    public function __construct(
        protected PackageResolver $resolver = new PackageResolver,
        protected StubRenderer $renderer = new StubRenderer,
        protected FilesystemWriter $writer = new FilesystemWriter,
        ?string $basePath = null
    ) {
        $this->basePath = $basePath ?? base_path();
        $this->resolver = new PackageResolver(basePath: $this->basePath);
    }

    /**
     * Generate optional Admin integration layer skeleton for the specified package.
     *
     * @return array{
     *     package: ResolvedPackage,
     *     dry_run: bool,
     *     force: bool,
     *     files: array<int, array{path: string, full_path: string, action: string, bytes: int}>
     * }
     */
    public function generate(
        string $packageInput,
        bool $dryRun = false,
        bool $force = false
    ): array {
        $resolved = $this->resolver->resolve($packageInput);
        $identity = $resolved->identity;

        // 1. Validate and prepare package composer.json mutation
        $composerPath = $resolved->packagePath . '/composer.json';
        if (! file_exists($composerPath) || ! is_readable($composerPath)) {
            throw PackageGenerationException::invalidInput("Package manifest [composer.json] is missing or not readable.");
        }

        $rawJson = (string) file_get_contents($composerPath);
        try {
            $composerData = json_decode($rawJson, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw PackageGenerationException::invalidInput("Package manifest [composer.json] contains invalid JSON: " . $e->getMessage());
        }

        if (! is_array($composerData)) {
            throw PackageGenerationException::invalidInput("Package manifest [composer.json] must contain a JSON object.");
        }

        if (! isset($composerData['extra']['laraseed']) || ! is_array($composerData['extra']['laraseed'])) {
            throw PackageGenerationException::invalidInput("Package manifest [composer.json] is missing extra.laraseed metadata.");
        }

        $existingCapabilities = $composerData['extra']['laraseed']['capabilities'] ?? null;
        if ($existingCapabilities !== null && ! is_array($existingCapabilities)) {
            throw PackageGenerationException::invalidInput("Package manifest [composer.json] has an invalid capabilities definition.");
        }

        if (isset($existingCapabilities['admin']) && ! $force) {
            throw PackageGenerationException::collisionDetected([
                "{$identity->relativePackagePath}/composer.json (capabilities.admin)",
            ]);
        }

        $adminProviderClass = $resolved->namespace . '\\Admin\\Providers\\AdminServiceProvider';
        if (! isset($composerData['extra']['laraseed']['capabilities']) || ! is_array($composerData['extra']['laraseed']['capabilities'])) {
            $composerData['extra']['laraseed']['capabilities'] = [];
        }
        $composerData['extra']['laraseed']['capabilities']['admin'] = [
            'provider' => $adminProviderClass,
            'enabled' => true,
        ];

        $newComposerJson = json_encode($composerData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";

        // 2. Prepare Admin integration files
        $packageKey = strtolower($identity->vendor) . '_' . $identity->packageSnake;
        $packageSlug = $identity->vendorKebab . '-' . $identity->packageKebab;
        $packageTitle = Str::headline($identity->package);

        $replacements = [
            '{{ PACKAGE_KEY }}'   => $packageKey,
            '{{ PACKAGE_SLUG }}'  => $packageSlug,
            '{{ PACKAGE_TITLE }}' => $packageTitle,
        ];

        $renderFile = function (string $stub) use ($identity, $replacements): string {
            $content = $this->renderer->render($stub, $identity);
            return str_replace(array_keys($replacements), array_values($replacements), $content);
        };

        $files = [
            'src/Admin/Providers/AdminServiceProvider.php' => $renderFile('admin_provider.php.stub'),
            'src/Admin/Config/menu.php'                    => $renderFile('admin_menu.php.stub'),
            'src/Admin/Config/acl.php'                     => $renderFile('admin_acl.php.stub'),
            'src/Admin/Http/Controllers/AdminController.php' => $renderFile('admin_controller.php.stub'),
            'src/Admin/Routes/web.php'                     => $renderFile('admin_routes_web.php.stub'),
            'src/Admin/Resources/lang/en/app.php'          => $renderFile('admin_lang_en.php.stub'),
            'src/Admin/Resources/lang/ar/app.php'          => $renderFile('admin_lang_ar.php.stub'),
            'src/Admin/Resources/views/index.blade.php'   => $renderFile('admin_view_index.blade.php.stub'),
        ];

        // 3. Preflight Admin files collision
        $plan = new GenerationPlan($identity->relativePackagePath, $this->basePath, $files);
        $plan->preflight($force);

        // 4. Execute atomic write / simulation with rollback safety
        if (! $dryRun) {
            $createdPaths = [];
            $overwrittenBackups = [];
            $originalComposer = $rawJson;

            try {
                $targetDir = $plan->targetDirectory();
                foreach ($plan->files as $relativePath => $content) {
                    $fullPath = "{$targetDir}/{$relativePath}";
                    if (file_exists($fullPath)) {
                        $overwrittenBackups[$fullPath] = (string) file_get_contents($fullPath);
                    } else {
                        $createdPaths[] = $fullPath;
                    }
                }

                $results = $this->writer->execute($plan, false, $force);

                $written = file_put_contents($composerPath, $newComposerJson);
                if ($written === false) {
                    throw new \RuntimeException("Failed to write to package composer.json at [{$composerPath}].");
                }
            } catch (\Throwable $e) {
                // Rollback: delete newly created files
                foreach ($createdPaths as $path) {
                    if (file_exists($path)) {
                        @unlink($path);
                    }
                }

                // Clean empty directories
                $adminDir = $resolved->packagePath . '/src/Admin';
                if (is_dir($adminDir)) {
                    $this->cleanEmptyDirectories($adminDir);
                }

                // Restore overwritten files from backup
                foreach ($overwrittenBackups as $path => $originalContent) {
                    @file_put_contents($path, $originalContent);
                }

                // Restore composer.json
                @file_put_contents($composerPath, $originalComposer);

                throw PackageGenerationException::invalidInput("Admin generation failed with transactional rollback: " . $e->getMessage());
            }
        } else {
            $results = $this->writer->execute($plan, true, $force);
        }

        $results[] = [
            'path' => "{$identity->relativePackagePath}/composer.json",
            'full_path' => $composerPath,
            'action' => 'UPDATE',
            'bytes' => strlen($newComposerJson),
        ];

        return [
            'package' => $resolved,
            'dry_run' => $dryRun,
            'force' => $force,
            'files' => $results,
        ];
    }

    protected function cleanEmptyDirectories(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = "{$dir}/{$item}";
            if (is_dir($path)) {
                $this->cleanEmptyDirectories($path);
            }
        }

        $remaining = array_diff(scandir($dir) ?: [], ['.', '..']);
        if ($remaining === []) {
            @rmdir($dir);
        }
    }
}
