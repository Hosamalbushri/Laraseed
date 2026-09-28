<?php

namespace Webkul\Web\Context;

use Webkul\Web\Contracts\WebContextContract;

class WebContext implements WebContextContract
{
    public function __construct(
        protected string $locale = 'en',
        protected string $direction = 'ltr',
        protected string $activeTheme = 'default',
        protected ?string $canonicalUrl = null,
    ) {}

    public function locale(): string
    {
        return $this->locale;
    }

    public function direction(): string
    {
        return $this->direction;
    }

    public function isRtl(): bool
    {
        return strtolower($this->direction) === 'rtl';
    }

    public function activeTheme(): string
    {
        return $this->activeTheme;
    }

    public function canonicalUrl(): ?string
    {
        return $this->canonicalUrl;
    }
}
