<?php

namespace Webkul\Web\Navigation;

use InvalidArgumentException;

/**
 * Immutable, request-independent definition of a translated navigation label.
 */
final class NavigationLabel
{
    /**
     * @param  array<string, string>  $replacements
     */
    private function __construct(
        public readonly string $translationKey,
        public readonly array $replacements = [],
    ) {}

    /**
     * Define a label that will be translated for the active Web request.
     *
     * @param  array<string, scalar|null>  $replacements
     */
    public static function translation(string $key, array $replacements = []): self
    {
        if (trim($key) === '') {
            throw new InvalidArgumentException('Navigation label translation key is required.');
        }

        $normalizedReplacements = [];

        foreach ($replacements as $name => $value) {
            if (! is_string($name) || $name === '') {
                throw new InvalidArgumentException('Navigation label replacement names must be non-empty strings.');
            }

            if (! is_scalar($value) && $value !== null) {
                throw new InvalidArgumentException("Navigation label replacement [{$name}] must be scalar or null.");
            }

            $normalizedReplacements[$name] = (string) ($value ?? '');
        }

        return new self($key, $normalizedReplacements);
    }

    /**
     * Return a deterministic representation suitable for inspection/serialization.
     *
     * @return array{translation_key: string, replacements: array<string, string>}
     */
    public function toArray(): array
    {
        return [
            'translation_key' => $this->translationKey,
            'replacements' => $this->replacements,
        ];
    }
}
