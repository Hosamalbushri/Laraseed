<?php

namespace Webkul\Website\Contracts;

use Webkul\Website\SiteDefinition\SiteDefinition;

interface SiteDefinitionContract
{
    /**
     * Resolve the immutable site definition for the active Web request locale
     * (or an explicitly supplied locale code).
     */
    public function current(?string $locale = null): SiteDefinition;

    /**
     * Resolve the immutable site definition for a specific locale code.
     */
    public function forLocale(string $locale): SiteDefinition;
}
