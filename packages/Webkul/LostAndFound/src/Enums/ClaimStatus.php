<?php

namespace Webkul\LostAndFound\Enums;

enum ClaimStatus: string
{
    case SUBMITTED = 'submitted';
    case UNDER_REVIEW = 'under_review';
    case NEEDS_INFORMATION = 'needs_information';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case WITHDRAWN = 'withdrawn';
}
