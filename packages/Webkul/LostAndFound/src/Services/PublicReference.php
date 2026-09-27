<?php

namespace Webkul\LostAndFound\Services;

use InvalidArgumentException;

class PublicReference
{
    /**
     * Format-neutral public reference value object.
     * Validates safe public reference properties without hardcoding year or sequence structure.
     */
    public function __construct(private string $value)
    {
        $trimmed = trim($value);

        if ($trimmed === '' || strlen($trimmed) < 3 || strlen($trimmed) > 64) {
            throw new InvalidArgumentException("Invalid public reference length: {$value}");
        }

        if (! preg_match('/^[A-Za-z0-9\-_]+$/', $trimmed)) {
            throw new InvalidArgumentException("Public reference contains unsafe characters: {$value}");
        }

        $this->value = $trimmed;
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    /**
     * Return the canonical, case-insensitive database lookup key.
     */
    public static function normalize(string $value): string
    {
        return strtolower(self::fromString($value)->getValue());
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
