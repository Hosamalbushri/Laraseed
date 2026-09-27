<?php

namespace Webkul\LostAndFound\Services;

use InvalidArgumentException;
use Webkul\LostAndFound\Enums\ClaimStatus;

class ClaimStateService
{
    private const ALLOWED_TRANSITIONS = [
        ClaimStatus::SUBMITTED->value => [
            ClaimStatus::UNDER_REVIEW->value,
            ClaimStatus::WITHDRAWN->value,
        ],
        ClaimStatus::UNDER_REVIEW->value => [
            ClaimStatus::NEEDS_INFORMATION->value,
            ClaimStatus::APPROVED->value,
            ClaimStatus::REJECTED->value,
            ClaimStatus::WITHDRAWN->value,
        ],
        ClaimStatus::NEEDS_INFORMATION->value => [
            ClaimStatus::UNDER_REVIEW->value,
            ClaimStatus::WITHDRAWN->value,
        ],
        ClaimStatus::APPROVED->value => [
            ClaimStatus::REJECTED->value,
        ],
        ClaimStatus::REJECTED->value => [],
        ClaimStatus::WITHDRAWN->value => [],
    ];

    public static function canTransition(ClaimStatus $from, ClaimStatus $to): bool
    {
        if ($from === $to) {
            return true;
        }

        $allowed = self::ALLOWED_TRANSITIONS[$from->value] ?? [];

        return in_array($to->value, $allowed, true);
    }

    public static function transition(ClaimStatus $from, ClaimStatus $to): ClaimStatus
    {
        if (! self::canTransition($from, $to)) {
            throw new InvalidArgumentException("Illegal Claim state transition from {$from->value} to {$to->value}");
        }

        return $to;
    }
}
