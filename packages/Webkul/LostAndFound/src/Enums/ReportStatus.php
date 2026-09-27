<?php

namespace Webkul\LostAndFound\Enums;

enum ReportStatus: string
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case RESOLVED = 'resolved';
    case CANCELLED = 'cancelled';
}
