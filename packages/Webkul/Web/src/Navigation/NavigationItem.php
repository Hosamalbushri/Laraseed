<?php

namespace Webkul\Web\Navigation;

use Closure;
use Illuminate\Http\Request;
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
        public readonly string|NavigationLabel $title,
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
     * Determine if this navigation item is active for the given or current request.
     */
    public function isActive(?Request $request = null): bool
    {
        $request ??= app()->bound('request') ? app('request') : null;

        if (! $request instanceof Request) {
            return false;
        }

        $activeRoutes = $this->attributes['active_routes'] ?? $this->attributes['route'] ?? null;
        if (! empty($activeRoutes) && $request->routeIs(...(array) $activeRoutes)) {
            return true;
        }

        $activePatterns = $this->attributes['active_patterns'] ?? null;
        if (! empty($activePatterns) && $request->is(...(array) $activePatterns)) {
            return true;
        }

        $url = trim($this->url);
        if ($url === '' || str_starts_with($url, '#')) {
            return false;
        }

        $urlHost = parse_url($url, PHP_URL_HOST);
        if (is_string($urlHost) && $urlHost !== '' && strcasecmp($urlHost, $request->getHost()) !== 0) {
            $localHosts = ['localhost', '127.0.0.1'];
            if (! in_array(strtolower($urlHost), $localHosts, true) || ! in_array(strtolower($request->getHost()), $localHosts, true)) {
                return false;
            }
        }

        $itemPath = trim((string) parse_url($url, PHP_URL_PATH), '/');
        $currentPath = trim($request->path(), '/');

        if ($itemPath === '') {
            return $currentPath === '';
        }

        return $currentPath === $itemPath || str_starts_with($currentPath, $itemPath.'/');
    }

    /**
     * Convert item to array representation.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title instanceof NavigationLabel
                ? $this->title->toArray()
                : $this->title,
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
