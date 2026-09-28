<?php

namespace Webkul\Core\Contracts;

use Webkul\Core\Enums\LocaleDirection;

interface Locale
{
    public function code(): string;

    public function direction(): LocaleDirection;

    public function isActive(): bool;
}
