<?php

namespace Webkul\LostAndFound\Services;

use InvalidArgumentException;
use Webkul\LostAndFound\Enums\ReportStatus;

class ReportStateService
{
    private const ALLOWED_TRANSITIONS = [
        ReportStatus::DRAFT->value => [
            ReportStatus::ACTIVE->value,
            ReportStatus::CANCELLED->value,
        ],
        ReportStatus::ACTIVE->value => [
            ReportStatus::RESOLVED->value,
            ReportStatus::CANCELLED->value,
        ],
        ReportStatus::RESOLVED->value => [],
        ReportStatus::CANCELLED->value => [],
    ];

    public static function canTransition(ReportStatus $from, ReportStatus $to): bool
    {
        if ($from === $to) {
            return true;
        }

        $allowed = self::ALLOWED_TRANSITIONS[$from->value] ?? [];

        return in_array($to->value, $allowed, true);
    }

    public static function transition(ReportStatus $from, ReportStatus $to): ReportStatus
    {
        if (! self::canTransition($from, $to)) {
            throw new InvalidArgumentException("Illegal Report state transition from {$from->value} to {$to->value}");
        }

        return $to;
    }
}
