<?php

namespace Webkul\LostAndFound\Enums;

enum ItemStatus: string
{
    case DRAFT = 'draft';
    case REPORTED = 'reported';
    case IN_CUSTODY = 'in_custody';
    case RETURNED = 'returned';
    case DISPOSED = 'disposed';

    public function isTerminal(): bool
    {
        return match ($this) {
            self::RETURNED, self::DISPOSED => true,
            default => false,
        };
    }
}
