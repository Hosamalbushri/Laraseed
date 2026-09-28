<?php

namespace Webkul\Theme\Contracts;

use Webkul\Theme\Definitions\ThemeDefinition;

interface ThemeResolverContract
{
    /**
     * Resolve the active theme definition for the current request context.
     */
    public function resolveActiveTheme(): ThemeDefinition;

    /**
     * Resolve the inheritance chain for the active theme.
     *
     * @return array<ThemeDefinition>
     */
    public function resolveActiveInheritanceChain(): array;

    /**
     * Override the active theme ID for the current request.
     */
    public function setActiveTheme(string $id): void;
}
