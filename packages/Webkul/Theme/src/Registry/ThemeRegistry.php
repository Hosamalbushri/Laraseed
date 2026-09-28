<?php

namespace Webkul\Theme\Registry;

use InvalidArgumentException;
use Webkul\Theme\Contracts\ThemeRegistryContract;
use Webkul\Theme\Definitions\ThemeDefinition;
use Webkul\Theme\Exceptions\MissingParentThemeException;
use Webkul\Theme\Exceptions\ThemeInheritanceCycleException;
use Webkul\Theme\Exceptions\ThemeNotFoundException;

class ThemeRegistry implements ThemeRegistryContract
{
    /**
     * Storage for registered themes keyed by normalized theme ID.
     *
     * @var array<string, ThemeDefinition>
     */
    protected array $themes = [];

    /**
     * Register a theme definition.
     */
    public function register(ThemeDefinition $theme): void
    {
        $id = $theme->id;

        if (isset($this->themes[$id])) {
            throw new InvalidArgumentException("Theme with ID [{$id}] is already registered.");
        }

        $this->themes[$id] = $theme;
    }

    /**
     * Check if a theme is registered.
     */
    public function has(string $id): bool
    {
        return isset($this->themes[strtolower(trim($id))]);
    }

    /**
     * Get a theme definition by its ID.
     */
    public function get(string $id): ThemeDefinition
    {
        $normalizedId = strtolower(trim($id));

        if (! isset($this->themes[$normalizedId])) {
            throw new ThemeNotFoundException("Theme [{$id}] is not registered.");
        }

        return $this->themes[$normalizedId];
    }

    /**
     * Get all registered theme definitions, sorted deterministically by ID.
     *
     * @return array<string, ThemeDefinition>
     */
    public function all(): array
    {
        $sorted = $this->themes;
        ksort($sorted);

        return $sorted;
    }

    /**
     * Resolve the full inheritance chain for a given theme ID.
     *
     * Returns an ordered array of ThemeDefinition objects starting from the child theme
     * down through its parent and ancestor chain.
     *
     * @return array<ThemeDefinition>
     */
    public function resolveInheritanceChain(string $id): array
    {
        $chain = [];
        $visited = [];
        $currentId = strtolower(trim($id));

        while ($currentId !== null) {
            if (isset($visited[$currentId])) {
                $cyclePath = implode(' -> ', array_merge(array_keys($visited), [$currentId]));
                throw new ThemeInheritanceCycleException(
                    "Cyclic theme inheritance detected: [{$cyclePath}]."
                );
            }

            if (! isset($this->themes[$currentId])) {
                if (empty($chain)) {
                    throw new ThemeNotFoundException("Theme [{$currentId}] is not registered.");
                }

                throw new MissingParentThemeException(
                    "Parent theme [{$currentId}] referenced by [{$chain[count($chain) - 1]->id}] is not registered."
                );
            }

            $visited[$currentId] = true;
            $theme = $this->themes[$currentId];
            $chain[] = $theme;

            $currentId = $theme->parent;
        }

        return $chain;
    }
}
