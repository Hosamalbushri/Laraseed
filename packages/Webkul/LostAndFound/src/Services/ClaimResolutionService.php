<?php

namespace Webkul\LostAndFound\Services;

use DomainException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Webkul\LostAndFound\Enums\ClaimStatus;
use Webkul\LostAndFound\Enums\ItemStatus;
use Webkul\LostAndFound\Models\ClaimReview;
use Webkul\LostAndFound\Models\FoundItem;
use Webkul\LostAndFound\Models\LostFoundClaim;

class ClaimResolutionService
{
    private const COMPETING_CLAIM_REASON = 'ownership_awarded_to_competing_claim';

    public function review(
        int $foundItemId,
        int $claimId,
        int $reviewerUserId,
        ClaimStatus $toStatus,
        ?string $claimantMessage = null,
        ?string $staffNotes = null,
    ): LostFoundClaim {
        if (! in_array($toStatus, [
            ClaimStatus::UNDER_REVIEW,
            ClaimStatus::NEEDS_INFORMATION,
            ClaimStatus::REJECTED,
        ], true)) {
            throw new InvalidArgumentException('Approval, withdrawal, and revocation require their dedicated operations.');
        }

        return DB::transaction(function () use (
            $foundItemId,
            $claimId,
            $reviewerUserId,
            $toStatus,
            $claimantMessage,
            $staffNotes,
        ): LostFoundClaim {
            $item = $this->lockItem($foundItemId);

            if ($item->approved_claim_id !== null) {
                throw new DomainException('No claim may be reviewed after ownership has been resolved.');
            }

            $claim = $this->claimFrom($this->lockClaims($foundItemId), $claimId);
            $fromStatus = $claim->status;

            if ($fromStatus === $toStatus) {
                throw new InvalidArgumentException('A review must change the claim state.');
            }

            $claim->status = ClaimStateService::transition($fromStatus, $toStatus);
            $claim->save();

            $this->appendReview(
                $claim,
                $reviewerUserId,
                $fromStatus,
                $toStatus,
                $claimantMessage,
                $staffNotes,
            );

            return $claim->refresh();
        });
    }

    public function approve(
        int $foundItemId,
        int $claimId,
        int $reviewerUserId,
        ?string $claimantMessage = null,
        ?string $staffNotes = null,
    ): LostFoundClaim {
        return DB::transaction(function () use (
            $foundItemId,
            $claimId,
            $reviewerUserId,
            $claimantMessage,
            $staffNotes,
        ): LostFoundClaim {
            $item = $this->lockItem($foundItemId);
            $this->assertItemCanAcceptApproval($item);

            $claims = $this->lockClaims($foundItemId);
            $approvedClaim = $this->claimFrom($claims, $claimId);

            if (! $approvedClaim->claimant()->exists()) {
                throw new DomainException('The claim does not have an existing student claimant.');
            }

            $fromStatus = $approvedClaim->status;
            $toStatus = ClaimStateService::transition($fromStatus, ClaimStatus::APPROVED);

            $pointerUpdated = DB::table('lost_found_items')
                ->where('id', $item->getKey())
                ->whereNull('approved_claim_id')
                ->update([
                    'approved_claim_id' => $approvedClaim->getKey(),
                    'updated_at' => now(),
                ]);

            if ($pointerUpdated !== 1) {
                throw new DomainException('This item already has an approved claim.');
            }

            $approvedClaim->status = $toStatus;
            $approvedClaim->save();
            $this->appendReview(
                $approvedClaim,
                $reviewerUserId,
                $fromStatus,
                $toStatus,
                $claimantMessage,
                $staffNotes,
            );

            foreach ($claims as $competingClaim) {
                if ($competingClaim->is($approvedClaim)) {
                    continue;
                }

                $this->rejectCompetingClaim($competingClaim, $reviewerUserId);
            }

            return $approvedClaim->refresh();
        });
    }

    public function withdraw(int $foundItemId, int $claimId): LostFoundClaim
    {
        return DB::transaction(function () use ($foundItemId, $claimId): LostFoundClaim {
            $item = $this->lockItem($foundItemId);
            $claim = $this->claimFrom($this->lockClaims($foundItemId), $claimId);

            if ((int) $item->approved_claim_id === $claimId) {
                throw new DomainException('An approved claim must be revoked by staff before it can be closed.');
            }

            $claim->status = ClaimStateService::transition($claim->status, ClaimStatus::WITHDRAWN);
            $claim->withdrawn_at = now();
            $claim->save();

            return $claim->refresh();
        });
    }

    public function revoke(
        int $foundItemId,
        int $claimId,
        int $reviewerUserId,
        ?string $claimantMessage = null,
        ?string $staffNotes = null,
    ): LostFoundClaim {
        return DB::transaction(function () use (
            $foundItemId,
            $claimId,
            $reviewerUserId,
            $claimantMessage,
            $staffNotes,
        ): LostFoundClaim {
            $item = $this->lockItem($foundItemId);
            $claim = $this->claimFrom($this->lockClaims($foundItemId), $claimId);

            if ($item->status === ItemStatus::RETURNED || $item->handover()->exists()) {
                throw new DomainException('An ownership decision cannot be revoked after physical handover.');
            }

            if ((int) $item->approved_claim_id !== $claimId) {
                throw new DomainException('The claim is not the approved claim for this item.');
            }

            $fromStatus = $claim->status;
            $toStatus = ClaimStateService::transition($fromStatus, ClaimStatus::REJECTED);
            $pointerCleared = DB::table('lost_found_items')
                ->where('id', $item->getKey())
                ->where('approved_claim_id', $claimId)
                ->update([
                    'approved_claim_id' => null,
                    'updated_at' => now(),
                ]);

            if ($pointerCleared !== 1) {
                throw new DomainException('The approved claim changed while revocation was in progress.');
            }

            $claim->status = $toStatus;
            $claim->save();
            $this->appendReview(
                $claim,
                $reviewerUserId,
                $fromStatus,
                $toStatus,
                $claimantMessage,
                $staffNotes,
            );

            return $claim->refresh();
        });
    }

    private function lockItem(int $foundItemId): FoundItem
    {
        return FoundItem::query()->lockForUpdate()->findOrFail($foundItemId);
    }

    /** @return Collection<int, LostFoundClaim> */
    private function lockClaims(int $foundItemId): Collection
    {
        return LostFoundClaim::query()
            ->where('found_item_id', $foundItemId)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }

    /** @param Collection<int, LostFoundClaim> $claims */
    private function claimFrom(Collection $claims, int $claimId): LostFoundClaim
    {
        $claim = $claims->first(static fn (LostFoundClaim $candidate): bool => $candidate->getKey() === $claimId);

        if (! $claim) {
            throw new DomainException('The claim does not belong to the locked found item.');
        }

        return $claim;
    }

    private function assertItemCanAcceptApproval(FoundItem $item): void
    {
        if ($item->approved_claim_id !== null) {
            throw new DomainException('This item already has an approved claim.');
        }

        if (! in_array($item->status, [ItemStatus::REPORTED, ItemStatus::IN_CUSTODY], true)) {
            throw new DomainException('This item cannot accept an ownership approval in its current state.');
        }
    }

    private function rejectCompetingClaim(LostFoundClaim $claim, int $reviewerUserId): void
    {
        if (in_array($claim->status, [ClaimStatus::REJECTED, ClaimStatus::WITHDRAWN], true)) {
            return;
        }

        if ($claim->status === ClaimStatus::APPROVED) {
            throw new DomainException('The item has more than one approved claim.');
        }

        if ($claim->status !== ClaimStatus::UNDER_REVIEW) {
            $fromStatus = $claim->status;
            $claim->status = ClaimStateService::transition($fromStatus, ClaimStatus::UNDER_REVIEW);
            $claim->save();
            $this->appendReview(
                $claim,
                $reviewerUserId,
                $fromStatus,
                ClaimStatus::UNDER_REVIEW,
                null,
                self::COMPETING_CLAIM_REASON,
            );
        }

        $fromStatus = $claim->status;
        $claim->status = ClaimStateService::transition($fromStatus, ClaimStatus::REJECTED);
        $claim->save();
        $this->appendReview(
            $claim,
            $reviewerUserId,
            $fromStatus,
            ClaimStatus::REJECTED,
            null,
            self::COMPETING_CLAIM_REASON,
        );
    }

    private function appendReview(
        LostFoundClaim $claim,
        int $reviewerUserId,
        ClaimStatus $fromStatus,
        ClaimStatus $toStatus,
        ?string $claimantMessage,
        ?string $staffNotes,
    ): ClaimReview {
        return $claim->reviews()->create([
            'reviewer_user_id' => $reviewerUserId,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'claimant_message' => $claimantMessage,
            'staff_notes' => $staffNotes,
            'reviewed_at' => now(),
        ]);
    }
}
