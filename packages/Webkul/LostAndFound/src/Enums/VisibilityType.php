<?php

namespace Webkul\LostAndFound\Enums;

enum VisibilityType: string
{
    case PUBLIC = 'public';
    case STUDENT_OWNER = 'student_owner';
    case CLAIMANT_ONLY = 'claimant_only';
    case STAFF_ONLY = 'staff_only';
}
