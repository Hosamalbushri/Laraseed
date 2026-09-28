<?php

namespace Webkul\Web\Navigation;

use Illuminate\Contracts\Translation\Translator;
use Webkul\Web\Contracts\WebContextContract;

/**
 * Resolves navigation definitions only after the current Web locale exists.
 */
final class NavigationLabelResolver
{
    public function __construct(
        private readonly Translator $translator,
        private readonly WebContextContract $webContext,
    ) {}

    public function resolve(string|NavigationLabel $label): string
    {
        if (is_string($label)) {
            return $label;
        }

        $resolved = $this->translator->get(
            $label->translationKey,
            $label->replacements,
            $this->webContext->locale(),
        );

        return is_string($resolved) ? $resolved : $label->translationKey;
    }
}
