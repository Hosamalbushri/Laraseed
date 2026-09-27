<?php

namespace Tests\Unit\LostAndFound;

use InvalidArgumentException;
use Tests\TestCase;
use Webkul\LostAndFound\Enums\ClaimStatus;
use Webkul\LostAndFound\Services\ClaimStateService;

class ClaimStateMachineTest extends TestCase
{
    public function test_valid_claim_state_transitions(): void
    {
        $this->assertTrue(ClaimStateService::canTransition(ClaimStatus::SUBMITTED, ClaimStatus::UNDER_REVIEW));
        $this->assertTrue(ClaimStateService::canTransition(ClaimStatus::UNDER_REVIEW, ClaimStatus::APPROVED));
    }

    public function test_invalid_claim_state_transitions_throw_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ClaimStateService::transition(ClaimStatus::APPROVED, ClaimStatus::SUBMITTED);
    }
}
