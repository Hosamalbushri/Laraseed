<?php

namespace Webkul\LostAndFound\Services;

use InvalidArgumentException;
use Webkul\LostAndFound\Enums\ItemStatus;

class ItemStateService
{
    private const ALLOWED_TRANSITIONS = [
        ItemStatus::DRAFT->value => [
            ItemStatus::REPORTED->value,
            ItemStatus::IN_CUSTODY->value,
        ],
        ItemStatus::REPORTED->value => [
            ItemStatus::IN_CUSTODY->value,
        ],
        ItemStatus::IN_CUSTODY->value => [
            ItemStatus::RETURNED->value,
            ItemStatus::DISPOSED->value,
        ],
        ItemStatus::RETURNED->value => [],
        ItemStatus::DISPOSED->value => [],
    ];

    public static function canTransition(ItemStatus $from, ItemStatus $to): bool
    {
        if ($from === $to) {
            return true;
        }

        $allowed = self::ALLOWED_TRANSITIONS[$from->value] ?? [];

        return in_array($to->value, $allowed, true);
    }

    public static function transition(ItemStatus $from, ItemStatus $to): ItemStatus
    {
        if (! self::canTransition($from, $to)) {
            throw new InvalidArgumentException("Illegal Item state transition from {$from->value} to {$to->value}");
        }

        return $to;
    }
}
