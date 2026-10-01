<?php

namespace Webkul\Core\Contracts;

use Illuminate\Support\Collection;
use Webkul\Core\Models\Locale;

interface ContentLocaleManager
{
    /**
     * Get all active content locales ordered deterministically.
     *
     * @return Collection<int, Locale>
     */
    public function activeContentLocales(): Collection;

    /**
     * Get the single primary content locale.
     */
    public function primaryContentLocale(): Locale;

    /**
     * Get all registered content locales ordered by sort order then code.
     *
     * @return Collection<int, Locale>
     */
    public function allContentLocales(): Collection;

    /**
     * Find a registered content locale by ID.
     */
    public function findLocale(int $id): Locale;

    /**
     * Validate, normalize, and create a new content locale.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function createLocale(array $attributes): Locale;

    /**
     * Validate and update editable metadata for a content locale.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function updateMetadata(int $id, array $attributes): Locale;

    /**
     * Activate a content locale.
     */
    public function activate(int $id): Locale;

    /**
     * Deactivate a content locale (fails if primary or last active).
     */
    public function deactivate(int $id): Locale;

    /**
     * Change the primary content locale atomically.
     */
    public function changePrimary(int $id): Locale;

    /**
     * Canonical validation rules for creating a content locale.
     *
     * @return array<string, mixed>
     */
    public function creationRules(): array;

    /**
     * Canonical validation rules for updating content locale metadata.
     *
     * @return array<string, mixed>
     */
    public function updateRules(): array;

    /**
     * Validate and normalize raw input for locale creation.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function validateCreation(array $attributes): array;

    /**
     * Validate raw input for locale metadata update.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function validateUpdate(array $attributes): array;
}
