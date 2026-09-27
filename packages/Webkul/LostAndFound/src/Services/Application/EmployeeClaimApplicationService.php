<?php

namespace Webkul\LostAndFound\Services\Application;

use Webkul\LostAndFound\Enums\ClaimStatus;
use Webkul\LostAndFound\Models\LostFoundClaim;
use Webkul\LostAndFound\Services\ClaimResolutionService;
use Webkul\User\Models\User;

class EmployeeClaimApplicationService
{
    public function __construct(
        protected ClaimResolutionService $claimResolutionService
    ) {}

    public function reviewClaim(User $actor, LostFoundClaim $claim, ClaimStatus $newStatus, ?string $reviewNotes = null): LostFoundClaim
    {
        LostAndFoundAuthorization::authorizeUser($actor, 'lost_found.claims.review');

        return $this->claimResolutionService->review($claim->found_item_id, $claim->id, $actor->id, $newStatus, null, $reviewNotes);
    }

    public function approveClaim(User $actor, LostFoundClaim $claim, ?string $reviewNotes = null): LostFoundClaim
    {
        LostAndFoundAuthorization::authorizeUser($actor, 'lost_found.claims.approve');

        return $this->claimResolutionService->approve($claim->found_item_id, $claim->id, $actor->id, null, $reviewNotes);
    }

    public function rejectClaim(User $actor, LostFoundClaim $claim, ?string $reviewNotes = null): LostFoundClaim
    {
        LostAndFoundAuthorization::authorizeUser($actor, 'lost_found.claims.reject');

        return $this->claimResolutionService->review($claim->found_item_id, $claim->id, $actor->id, ClaimStatus::REJECTED, null, $reviewNotes);
    }

    public function revokeClaimApproval(User $actor, LostFoundClaim $claim, ?string $reason = null): LostFoundClaim
    {
        LostAndFoundAuthorization::authorizeUser($actor, 'lost_found.claims.approve');

        return $this->claimResolutionService->revoke($claim->found_item_id, $claim->id, $actor->id, $reason);
    }
}
