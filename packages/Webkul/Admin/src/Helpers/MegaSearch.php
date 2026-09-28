<?php

namespace Webkul\Admin\Helpers;

class MegaSearch
{
    /** @var array<string, array{key: string, title: string, route: string, priority: int}> */
    protected array $tabs = [];

    public function register(string $key, string $title, string $route, int $priority = 100): void
    {
        $this->tabs[$key] = compact('key', 'title', 'route', 'priority');
    }

    public function tabs(): array
    {
        $tabs = $this->tabs;

        uasort($tabs, fn (array $left, array $right): int => $left['priority'] <=> $right['priority']);

        return array_map(function (array $tab): array {
            $tab['endpoint'] = route($tab['route']);

            unset($tab['priority']);
            unset($tab['route']);

            return $tab;
        }, $tabs);
    }
}
