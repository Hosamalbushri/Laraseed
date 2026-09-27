<?php

namespace Webkul\LostAndFound\Services\Application;

use Webkul\LostAndFound\Models\Handover;
use Webkul\LostAndFound\Models\LostFoundClaim;
use Webkul\LostAndFound\Services\HandoverService;
use Webkul\Student\Models\Student;
use Webkul\User\Models\User;

class EmployeeHandoverApplicationService
{
    public function __construct(
        protected HandoverService $handoverService
    ) {}

    public function completeHandover(
        User $actor,
        LostFoundClaim $claim,
        Student $recipientStudent,
        array $verificationData
    ): Handover {
        LostAndFoundAuthorization::authorizeUser($actor, 'lost_found.handover.complete');

        $verificationMethod = $verificationData['verification_method'] ?? 'In-person Student ID check';
        $verificationNote = $verificationData['verification_note'] ?? null;

        return $this->handoverService->complete(
            $claim->found_item_id,
            $claim->id,
            $recipientStudent->id,
            $actor->id,
            $verificationMethod,
            $verificationNote
        );
    }
}
