<?php

namespace Webkul\Theme\Contracts;

use Webkul\Theme\Definitions\ThemeDefinition;

interface ThemeRegistryContract
{
    /**
     * Register a theme definition.
     */
    public function register(ThemeDefinition $theme): void;

    /**
     * Check if a theme is registered.
     */
    public function has(string $id): bool;

    /**
     * Get a theme definition by its ID.
     */
    public function get(string $id): ThemeDefinition;

    /**
     * Get all registered theme definitions.
     *
     * @return array<string, ThemeDefinition>
     */
    public function all(): array;

    /**
     * Resolve the full inheritance chain for a given theme ID.
     *
     * @return array<ThemeDefinition>
     */
    public function resolveInheritanceChain(string $id): array;
}
