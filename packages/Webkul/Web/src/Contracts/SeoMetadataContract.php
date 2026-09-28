<?php

namespace Webkul\Web\Contracts;

interface SeoMetadataContract
{
    /**
     * Set page title.
     */
    public function setTitle(?string $title): static;

    /**
     * Get page title.
     */
    public function getTitle(): string;

    /**
     * Set page description.
     */
    public function setDescription(?string $description): static;

    /**
     * Get page description.
     */
    public function getDescription(): ?string;

    /**
     * Set canonical URL.
     */
    public function setCanonicalUrl(?string $url): static;

    /**
     * Get canonical URL.
     */
    public function getCanonicalUrl(): ?string;

    /**
     * Set OpenGraph / Twitter meta property.
     */
    public function setMeta(string $name, string $content): static;

    /**
     * Get all registered meta tags.
     */
    public function getMetas(): array;

    /**
     * Render complete HTML head tag string.
     */
    public function renderHeadHtml(): string;
}
