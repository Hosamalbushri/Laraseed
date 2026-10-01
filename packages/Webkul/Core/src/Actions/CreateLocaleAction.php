<?php

namespace Webkul\Core\Actions;

use Webkul\Core\Contracts\ContentLocaleManager;
use Webkul\Core\Models\Locale;

class CreateLocaleAction
{
    public function __construct(
        protected ContentLocaleManager $localeManager,
    ) {}

    /**
     * Validate, normalize, and execute creation of a new content locale.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function execute(array $attributes): Locale
    {
        return $this->localeManager->createLocale($attributes);
    }
}
