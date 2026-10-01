<?php

namespace Laraseed\PackageGenerator\Generators;

use Illuminate\Support\Str;
use JsonException;
use Laraseed\PackageGenerator\Exceptions\PackageGenerationException;
use Laraseed\PackageGenerator\Support\PackageResolver;
use Laraseed\PackageGenerator\Support\ResolvedPackage;
use Laraseed\PackageGenerator\Templates\WebTemplateCatalog;

class WebGenerator
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
     * Generate optional Web capability skeleton for the specified package.
     *
     * @return array{
     *     package: ResolvedPackage,
     *     template: string,
     *     dry_run: bool,
     *     files: array<int, array{path: string, full_path: string, action: string, bytes: int}>
     * }
     */
    public function generate(
        string $packageInput,
        string $template = WebTemplateCatalog::DEFAULT_TEMPLATE,
        bool $dryRun = false
    ): array {
        $templateData = WebTemplateCatalog::get($template);
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

        if (isset($existingCapabilities['web'])) {
            throw PackageGenerationException::collisionDetected([
                "{$identity->relativePackagePath}/composer.json (capabilities.web)",
            ]);
        }

        $webProviderClass = $resolved->namespace . '\\Web\\Providers\\WebServiceProvider';
        if (! isset($composerData['extra']['laraseed']['capabilities']) || ! is_array($composerData['extra']['laraseed']['capabilities'])) {
            $composerData['extra']['laraseed']['capabilities'] = [];
        }
        $composerData['extra']['laraseed']['capabilities']['web'] = [
            'provider' => $webProviderClass,
            'enabled' => true,
        ];

        $newComposerJson = json_encode($composerData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";

        // 2. Prepare Web capability files from template
        $packageKey = strtolower($identity->vendor) . '_' . $identity->packageSnake;
        $packageSlug = $identity->vendorKebab . '-' . $identity->packageKebab;
        $packageTitle = Str::headline($identity->package);
        $upperPackageKey = strtoupper($packageKey);

        $replacements = [
            '{{ PACKAGE_KEY }}'       => $packageKey,
            '{{ PACKAGE_SLUG }}'      => $packageSlug,
            '{{ PACKAGE_TITLE }}'     => $packageTitle,
            '{{ UPPER_PACKAGE_KEY }}' => $upperPackageKey,
        ];

        $renderFile = function (string $stub) use ($identity, $replacements): string {
            $content = $this->renderer->render($stub, $identity);
            return str_replace(array_keys($replacements), array_values($replacements), $content);
        };

        $files = [];
        foreach ($templateData['files'] as $destPath => $stubPath) {
            $files[$destPath] = $renderFile($stubPath);
        }

        // 3. Preflight Web files collision (strict without force)
        $plan = new GenerationPlan($identity->relativePackagePath, $this->basePath, $files);
        $plan->preflight(false);

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

                $results = $this->writer->execute($plan, false, false);

                $written = file_put_contents($composerPath, $newComposerJson);
                if ($written === false) {
                    throw new \RuntimeException("Failed to write to package composer.json at [{$composerPath}].");
                }
            } catch (\Throwable $e) {
                // Transactional Rollback
                foreach ($createdPaths as $created) {
                    if (file_exists($created)) {
                        @unlink($created);
                    }
                }

                foreach ($overwrittenBackups as $path => $originalContent) {
                    @file_put_contents($path, $originalContent);
                }

                if (file_exists($composerPath)) {
                    @file_put_contents($composerPath, $originalComposer);
                }

                // Clean up empty directories created during failed attempt
                $this->cleanupEmptyDirectories("{$resolved->packagePath}/src/Web");

                throw PackageGenerationException::invalidInput(
                    "Web capability generation failed with transactional rollback: " . $e->getMessage()
                );
            }
        } else {
            $results = $this->writer->execute($plan, true, false);
        }

        $results[] = [
            'path' => "{$identity->relativePackagePath}/composer.json",
            'full_path' => $composerPath,
            'action' => 'UPDATE',
            'bytes' => strlen($newComposerJson),
        ];

        return [
            'package' => $resolved,
            'template' => $template,
            'dry_run' => $dryRun,
            'files' => $results,
        ];
    }

    /**
     * Clean up empty directories recursively.
     */
    protected function cleanupEmptyDirectories(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        if ($items === false) {
            return;
        }

        $items = array_diff($items, ['.', '..']);
        foreach ($items as $item) {
            $path = "{$dir}/{$item}";
            if (is_dir($path)) {
                $this->cleanupEmptyDirectories($path);
            }
        }

        $remaining = array_diff(scandir($dir) ?: [], ['.', '..']);
        if (empty($remaining)) {
            @rmdir($dir);
        }
    }
}
