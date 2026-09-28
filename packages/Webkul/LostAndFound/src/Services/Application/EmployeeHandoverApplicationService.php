<?php

namespace Webkul\LostAndFound\Services\Application;

use DomainException;
use Webkul\LostAndFound\Models\FoundItem;
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
        FoundItem $item,
        array $verificationData
    ): Handover {
        LostAndFoundAuthorization::authorizeUser($actor, 'lost_found.handover.complete');

        $claim = $this->authoritativeClaim($item);
        $recipientStudent = $this->authoritativeRecipient($claim);
        $verificationMethod = $verificationData['verification_method'] ?? '';
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

    private function authoritativeClaim(FoundItem $item): LostFoundClaim
    {
        if ($item->approved_claim_id === null) {
            throw new DomainException('Physical handover requires an authoritative approved claim pointer.');
        }

        $claim = LostFoundClaim::query()
            ->whereKey($item->approved_claim_id)
            ->where('found_item_id', $item->getKey())
            ->first();

        if (! $claim) {
            throw new DomainException('The approved claim pointer does not identify a claim belonging to this item.');
        }

        return $claim;
    }

    private function authoritativeRecipient(LostFoundClaim $claim): Student
    {
        $recipient = Student::query()->find($claim->claimant_student_id);

        if (! $recipient) {
            throw new DomainException('The approved student claimant no longer exists.');
        }

        return $recipient;
    }
}
