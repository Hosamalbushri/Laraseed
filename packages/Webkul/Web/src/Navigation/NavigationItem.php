<?php

namespace Webkul\Web\Navigation;

use Closure;
use Illuminate\Support\Collection;

class NavigationItem
{
    /**
     * Child navigation items.
     *
     * @var Collection<int, NavigationItem>
     */
    public Collection $children;

    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly string $url,
        public readonly string $location = 'header',
        public readonly int $order = 100,
        public readonly ?string $parentId = null,
        public readonly ?string $icon = null,
        public readonly ?string $target = null,
        public readonly bool|Closure $visible = true,
        public readonly array $attributes = [],
    ) {
        $this->children = new Collection;
    }

    /**
     * Determine if this navigation item is visible in current context.
     */
    public function isVisible(): bool
    {
        if (is_callable($this->visible)) {
            return (bool) call_user_func($this->visible);
        }

        return (bool) $this->visible;
    }

    /**
     * Convert item to array representation.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'url' => $this->url,
            'location' => $this->location,
            'order' => $this->order,
            'parent_id' => $this->parentId,
            'icon' => $this->icon,
            'target' => $this->target,
            'visible' => $this->isVisible(),
            'attributes' => $this->attributes,
            'children' => $this->children->map(fn (NavigationItem $child) => $child->toArray())->values()->all(),
        ];
    }
}
