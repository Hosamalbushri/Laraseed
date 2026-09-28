<?php

namespace Webkul\Theme\Resolution;

use Webkul\Theme\Contracts\ThemeRegistryContract;
use Webkul\Theme\Contracts\ThemeResolverContract;
use Webkul\Theme\Definitions\ThemeDefinition;

class ThemeResolver implements ThemeResolverContract
{
    /**
     * Explicit active theme ID override for this request.
     */
    protected ?string $activeThemeId = null;

    /**
     * Create a new ThemeResolver instance.
     */
    public function __construct(
        protected ThemeRegistryContract $registry,
        protected ?string $defaultThemeId = null
    ) {
        $this->defaultThemeId = $defaultThemeId ?? (string) config('themes.active', 'default');
    }

    /**
     * Resolve the active theme definition for the current request context.
     */
    public function resolveActiveTheme(): ThemeDefinition
    {
        $themeId = $this->activeThemeId ?? $this->defaultThemeId ?? (string) config('themes.active', 'default');

        if ($this->registry->has($themeId)) {
            return $this->registry->get($themeId);
        }

        $fallbackId = (string) config('themes.fallback', 'default');

        if ($this->registry->has($fallbackId)) {
            return $this->registry->get($fallbackId);
        }

        return new ThemeDefinition(
            id: 'default',
            name: 'Default Theme',
            basePath: resource_path('views'),
            viewsPath: resource_path('views'),
        );
    }

    /**
     * Resolve the inheritance chain for the active theme.
     *
     * @return array<ThemeDefinition>
     */
    public function resolveActiveInheritanceChain(): array
    {
        $activeTheme = $this->resolveActiveTheme();

        if ($this->registry->has($activeTheme->id)) {
            return $this->registry->resolveInheritanceChain($activeTheme->id);
        }

        return [$activeTheme];
    }

    /**
     * Override the active theme ID for the current request.
     */
    public function setActiveTheme(string $id): void
    {
        $this->activeThemeId = strtolower(trim($id));
    }
}
