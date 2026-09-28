<?php

namespace Webkul\Theme\View;

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\FileViewFinder;
use InvalidArgumentException;
use Webkul\Theme\Definitions\ThemeDefinition;

class ThemeViewFinder extends FileViewFinder
{
    /**
     * Active theme inheritance chain (ordered child -> parent -> grandparent).
     *
     * @var array<ThemeDefinition>
     */
    protected array $activeThemeChain = [];

    /**
     * Set the active theme inheritance chain.
     *
     * @param  array<ThemeDefinition>  $chain
     */
    public function setActiveThemeChain(array $chain): void
    {
        $this->activeThemeChain = $chain;
    }

    /**
     * Get the active theme inheritance chain.
     *
     * @return array<ThemeDefinition>
     */
    public function getActiveThemeChain(): array
    {
        return $this->activeThemeChain;
    }

    /**
     * Namespaces strictly protected from theme overrides.
     *
     * @var array<string>
     */
    protected const PROTECTED_NAMESPACES = [
        'admin',
        'mail',
        'notifications',
        'errors',
    ];

    /**
     * Find a namespaced view, searching the active theme override hierarchy first.
     *
     * Resolution order for `namespace::view.name`:
     * 1. Child theme overrides directory: `{childThemeViewsPath}/overrides/{namespace}/{view}.blade.php`
     * 2. Parent theme overrides directory: `{parentThemeViewsPath}/overrides/{namespace}/{view}.blade.php`
     * 3. Package original registered hint paths
     *
     * @param  string  $name
     * @return string
     */
    protected function findNamespacedView($name)
    {
        [$namespace, $view] = $this->parseNamespaceSegments($name);

        if (in_array($namespace, self::PROTECTED_NAMESPACES, true)) {
            return $this->findInPaths($view, $this->hints[$namespace] ?? []);
        }

        $overridePaths = $this->buildThemeOverridePathsForNamespace($namespace);

        if (! empty($overridePaths)) {
            try {
                return $this->findInPaths($view, $overridePaths);
            } catch (InvalidArgumentException) {
                // Not found in active theme overrides; fall through to standard hint paths.
            }
        }

        return $this->findInPaths($view, $this->hints[$namespace] ?? []);
    }

    /**
     * Find a non-namespaced view, searching the active theme directory hierarchy first.
     *
     * @param  string  $name
     * @return string
     */
    protected function findPathView($name)
    {
        $themeViewPaths = [];

        foreach ($this->activeThemeChain as $theme) {
            if (is_dir($theme->viewsPath)) {
                $themeViewPaths[] = $theme->viewsPath;
            }
        }

        $allPaths = array_merge($themeViewPaths, $this->paths);

        return $this->findInPaths($name, $allPaths);
    }

    /**
     * Build candidate override directory paths for a package namespace across the active theme chain.
     *
     * @return array<string>
     */
    protected function buildThemeOverridePathsForNamespace(string $namespace): array
    {
        $paths = [];

        foreach ($this->activeThemeChain as $theme) {
            $candidatePath = $theme->viewsPath . '/overrides/' . $namespace;

            if (is_dir($candidatePath)) {
                $paths[] = $candidatePath;
            }
        }

        return $paths;
    }
}
