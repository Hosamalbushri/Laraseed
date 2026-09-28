<?php

namespace Webkul\Web\Navigation;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use Webkul\Web\Contracts\NavigationRegistryContract;

class NavigationRegistry implements NavigationRegistryContract
{
    /**
     * Supported navigation locations.
     */
    public const SUPPORTED_LOCATIONS = ['header', 'footer', 'mobile', 'secondary'];

    /**
     * Registered navigation items by location.
     *
     * @var array<string, array<string, NavigationItem>>
     */
    protected array $items = [];

    public function __construct()
    {
        foreach (self::SUPPORTED_LOCATIONS as $loc) {
            $this->items[$loc] = [];
        }
    }

    /**
     * Register a navigation item.
     */
    public function register(array $item): static
    {
        $id = (string) ($item['id'] ?? '');
        $title = $item['title'] ?? '';
        $url = (string) ($item['url'] ?? '');
        $location = (string) ($item['location'] ?? 'header');

        if (empty($id)) {
            throw new InvalidArgumentException('Navigation item ID is required.');
        }

        if (! is_string($title) && ! $title instanceof NavigationLabel) {
            throw new InvalidArgumentException("Navigation item title must be a string or NavigationLabel for [{$id}].");
        }

        if (is_string($title) && empty($title)) {
            throw new InvalidArgumentException("Navigation item title is required for [{$id}].");
        }

        if (! in_array($location, self::SUPPORTED_LOCATIONS, true)) {
            throw new InvalidArgumentException("Unsupported navigation location [{$location}].");
        }

        if (isset($this->items[$location][$id])) {
            throw new InvalidArgumentException("Navigation item [{$id}] is already registered in [{$location}].");
        }

        $this->items[$location][$id] = new NavigationItem(
            id: $id,
            title: $title,
            url: $url,
            location: $location,
            order: (int) ($item['order'] ?? 100),
            parentId: isset($item['parent_id']) ? (string) $item['parent_id'] : null,
            icon: isset($item['icon']) ? (string) $item['icon'] : null,
            target: isset($item['target']) ? (string) $item['target'] : null,
            visible: $item['visible'] ?? true,
            attributes: (array) ($item['attributes'] ?? []),
        );

        return $this;
    }

    /**
     * Get all items for a given location, sorted by order ASC, then id ASC.
     */
    public function getItems(string $location = 'header'): Collection
    {
        if (! in_array($location, self::SUPPORTED_LOCATIONS, true)) {
            return collect();
        }

        return collect($this->items[$location])
            ->filter(fn (NavigationItem $item) => $item->isVisible())
            ->sort(function (NavigationItem $a, NavigationItem $b) {
                if ($a->order === $b->order) {
                    return strcmp($a->id, $b->id);
                }

                return $a->order <=> $b->order;
            })
            ->values();
    }

    /**
     * Get hierarchical tree for a given location.
     */
    public function getTree(string $location = 'header'): Collection
    {
        $allItems = $this->getItems($location);

        $lookup = [];
        $roots = collect();

        foreach ($allItems as $item) {
            $clone = clone $item;
            $clone->children = new Collection;
            $lookup[$clone->id] = $clone;
        }

        foreach ($lookup as $item) {
            if ($item->parentId && isset($lookup[$item->parentId])) {
                $lookup[$item->parentId]->children->push($item);
            } else {
                $roots->push($item);
            }
        }

        return $roots;
    }

    /**
     * Check if item exists in given location.
     */
    public function has(string $id, string $location = 'header'): bool
    {
        return isset($this->items[$location][$id]);
    }
}
