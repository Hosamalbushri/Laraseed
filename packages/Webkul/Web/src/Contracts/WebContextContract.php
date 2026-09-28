<?php

namespace Webkul\Web\Contracts;

interface WebContextContract
{
    /**
     * Get the active web presentation locale code.
     */
    public function locale(): string;

    /**
     * Get the text direction (ltr or rtl).
     */
    public function direction(): string;

    /**
     * Determine if current direction is RTL.
     */
    public function isRtl(): bool;

    /**
     * Get the active visual theme identifier.
     */
    public function activeTheme(): string;

    /**
     * Get canonical URL for current request context.
     */
    public function canonicalUrl(): ?string;
}
