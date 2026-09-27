<?php

namespace Webkul\LostAndFound\Enums;

enum CustodyEventType: string
{
    case LOGGED = 'logged';
    case STORAGE_LOCATION_CHANGED = 'storage_location_changed';
    case TRANSFERRED = 'transferred';
    case HANDED_OVER = 'handed_over';
}
