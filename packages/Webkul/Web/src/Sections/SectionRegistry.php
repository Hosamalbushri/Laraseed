<?php

namespace Webkul\Web\Sections;

use Closure;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Webkul\Web\Contracts\SectionRegistryContract;

class SectionRegistry implements SectionRegistryContract
{
    /**
     * Registered sections grouped by page.
     *
     * @var array<string, array<string, array>>
     */
    protected array $sections = [];

    /**
     * Register a page section.
     */
    public function register(array $section): static
    {
        $page = (string) ($section['page'] ?? '');
        $key = (string) ($section['key'] ?? '');
        $view = (string) ($section['view'] ?? '');

        if (empty($page)) {
            throw new InvalidArgumentException('Section page identifier is required.');
        }

        if (empty($key)) {
            throw new InvalidArgumentException('Section key is required.');
        }

        if (empty($view)) {
            throw new InvalidArgumentException("Section view is required for [{$key}].");
        }

        if (! isset($this->sections[$page])) {
            $this->sections[$page] = [];
        }

        if (isset($this->sections[$page][$key])) {
            throw new InvalidArgumentException("Section [{$key}] is already registered on page [{$page}].");
        }

        $this->sections[$page][$key] = [
            'page' => $page,
            'key' => $key,
            'view' => $view,
            'order' => (int) ($section['order'] ?? 100),
            'data' => $section['data'] ?? [],
            'visible' => $section['visible'] ?? true,
        ];

        return $this;
    }

    /**
     * Get all registered sections for a page, sorted by order ASC, then key ASC.
     */
    public function getSections(string $page): Collection
    {
        if (! isset($this->sections[$page])) {
            return collect();
        }

        return collect($this->sections[$page])
            ->filter(function (array $section) {
                $visible = $section['visible'];
                if (is_callable($visible)) {
                    return (bool) call_user_func($visible);
                }

                return (bool) $visible;
            })
            ->sort(function (array $a, array $b) {
                if ($a['order'] === $b['order']) {
                    return strcmp($a['key'], $b['key']);
                }

                return $a['order'] <=> $b['order'];
            })
            ->map(function (array $section) {
                $data = $section['data'];
                if (is_callable($data)) {
                    $data = call_user_func($data);
                }

                return [
                    'page' => $section['page'],
                    'key' => $section['key'],
                    'view' => $section['view'],
                    'order' => $section['order'],
                    'data' => (array) $data,
                ];
            })
            ->values();
    }

    /**
     * Check if a section exists for a given page.
     */
    public function has(string $page, string $key): bool
    {
        return isset($this->sections[$page][$key]);
    }
}
