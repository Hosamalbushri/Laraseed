<?php

namespace Webkul\Web\Contracts;

use Illuminate\Support\Collection;

interface SectionRegistryContract
{
    /**
     * Register a page section.
     *
     * @param array{
     *     page: string,
     *     key: string,
     *     view: string,
     *     order?: int,
     *     data?: array|\Closure,
     *     visible?: bool|\Closure
     * } $section
     */
    public function register(array $section): static;

    /**
     * Get all registered sections for a page, sorted deterministically by order ASC, then key ASC.
     */
    public function getSections(string $page): Collection;

    /**
     * Check if a section exists for a given page.
     */
    public function has(string $page, string $key): bool;
}
