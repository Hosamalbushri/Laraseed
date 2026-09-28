<?php

namespace Webkul\Web\Contracts;

use Illuminate\Support\Collection;
use Webkul\Web\Navigation\NavigationItem;

interface NavigationRegistryContract
{
    /**
     * Register a navigation item.
     *
     * @param array{
     *     id: string,
     *     title: string,
     *     url: string,
     *     location?: string,
     *     order?: int,
     *     parent_id?: ?string,
     *     icon?: ?string,
     *     target?: ?string,
     *     visible?: bool|\Closure,
     *     attributes?: array
     * } $item
     */
    public function register(array $item): static;

    /**
     * Get all registered navigation items for a given location, sorted deterministically.
     */
    public function getItems(string $location = 'header'): Collection;

    /**
     * Get hierarchical navigation tree for a given location.
     */
    public function getTree(string $location = 'header'): Collection;

    /**
     * Check if a navigation item with given ID exists in a location.
     */
    public function has(string $id, string $location = 'header'): bool;
}
