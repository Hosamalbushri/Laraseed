<?php

namespace Tests\Unit\LostAndFound;

use InvalidArgumentException;
use Tests\TestCase;
use Webkul\LostAndFound\Enums\ClaimStatus;
use Webkul\LostAndFound\Enums\ItemStatus;
use Webkul\LostAndFound\Services\SecurityInvariants;

class SecurityInvariantsTest extends TestCase
{
    public function test_auth_secrets_prohibition(): void
    {
        $this->expectException(InvalidArgumentException::class);
        SecurityInvariants::assertNoAuthSecrets('The unlock PIN for my device is 1234');
    }

    public function test_handover_requires_approved_claim(): void
    {
        $this->expectException(InvalidArgumentException::class);
        SecurityInvariants::assertHandoverEligible(ItemStatus::IN_CUSTODY, ClaimStatus::UNDER_REVIEW);
    }

    public function test_handover_eligible_when_approved(): void
    {
        $this->expectNotToPerformAssertions();
        SecurityInvariants::assertHandoverEligible(ItemStatus::IN_CUSTODY, ClaimStatus::APPROVED);
    }
}
