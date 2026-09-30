<?php

namespace Tests\Unit\LostAndFound;

use InvalidArgumentException;
use Tests\TestCase;
use Webkul\LostAndFound\Services\PublicReference;

class PublicReferenceTest extends TestCase
{
    public function test_valid_public_reference(): void
    {
        $ref = PublicReference::fromString('LF-2026-000142');
        $this->assertEquals('LF-2026-000142', $ref->getValue());
        $this->assertEquals('LF-2026-000142', (string) $ref);
    }

    public function test_invalid_public_reference_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PublicReference('INV!');
    }

    public function test_public_reference_normalization_is_canonical_and_case_insensitive(): void
    {
        $this->assertSame('abc-123', PublicReference::normalize(' ABC-123 '));
        $this->assertSame('abc-123', PublicReference::normalize('abc-123'));
    }
}
