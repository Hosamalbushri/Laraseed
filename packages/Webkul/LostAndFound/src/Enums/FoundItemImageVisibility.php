<?php

namespace Webkul\LostAndFound\Enums;

enum FoundItemImageVisibility: string
{
    case PUBLIC_SAFE = 'public_safe';
    case STAFF_ONLY = 'staff_only';
}
