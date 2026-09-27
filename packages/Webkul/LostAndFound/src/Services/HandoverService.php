<?php

namespace Webkul\LostAndFound\Services;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use DomainException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Webkul\LostAndFound\Enums\ClaimStatus;
use Webkul\LostAndFound\Enums\CustodyEventType;
use Webkul\LostAndFound\Enums\ItemStatus;
use Webkul\LostAndFound\Models\CustodyRecord;
use Webkul\LostAndFound\Models\FoundItem;
use Webkul\LostAndFound\Models\Handover;
use Webkul\LostAndFound\Models\LostFoundClaim;
use Webkul\Student\Models\Student;
use Webkul\User\Models\UserProxy;

class HandoverService
{
    public function complete(
        int $foundItemId,
        int $claimId,
        int $recipientStudentId,
        int $staffUserId,
        string $verificationMethod,
        ?string $verificationNote = null,
        ?DateTimeInterface $handedOverAt = null,
    ): Handover {
        $verificationMethod = trim($verificationMethod);
        $verificationNote = $verificationNote !== null ? trim($verificationNote) : null;

        if ($verificationMethod === '') {
            throw new InvalidArgumentException('A non-secret recipient verification method is required.');
        }

        SecurityInvariants::assertNoAuthSecrets($verificationMethod);

        if ($verificationNote !== null && $verificationNote !== '') {
            SecurityInvariants::assertNoAuthSecrets($verificationNote);
        } else {
            $verificationNote = null;
        }

        $handedOverAt = $handedOverAt
            ? CarbonImmutable::instance($handedOverAt)
            : CarbonImmutable::now();

        return DB::transaction(function () use (
            $foundItemId,
            $claimId,
            $recipientStudentId,
            $staffUserId,
            $verificationMethod,
            $verificationNote,
            $handedOverAt,
        ): Handover {
            $item = FoundItem::query()->lockForUpdate()->findOrFail($foundItemId);

            if ($item->status !== ItemStatus::IN_CUSTODY) {
                throw new DomainException('Physical handover requires an item currently in institutional custody.');
            }

            if ($item->approved_claim_id === null) {
                throw new DomainException('Physical handover requires an authoritative approved claim pointer.');
            }

            $claims = $this->lockClaims($item);
            $approvedClaim = $claims->first(
                static fn (LostFoundClaim $claim): bool => $claim->getKey() === (int) $item->approved_claim_id,
            );

            if (! $approvedClaim) {
                throw new DomainException('The approved claim pointer does not identify a claim belonging to this item.');
            }

            if ($approvedClaim->getKey() !== $claimId) {
                throw new DomainException('The supplied claim is not the item authoritative approved claim.');
            }

            if ($approvedClaim->status !== ClaimStatus::APPROVED) {
                throw new DomainException('The authoritative claim is not in the approved state.');
            }

            if ((int) $approvedClaim->claimant_student_id !== $recipientStudentId) {
                throw new DomainException('The physical recipient must be the approved student claimant.');
            }

            $latestCustody = $this->lockLatestCustodyRecord($item);
            $this->assertCurrentCustody($item, $latestCustody);

            if (Handover::query()
                ->where('found_item_id', $item->getKey())
                ->orWhere('claim_id', $approvedClaim->getKey())
                ->lockForUpdate()
                ->exists()) {
                throw new DomainException('A handover already exists for this item or claim.');
            }

            $this->lockAndValidateActiveStaff($staffUserId);
            $recipient = Student::query()->lockForUpdate()->find($approvedClaim->claimant_student_id);

            if (! $recipient || $recipient->getKey() !== $recipientStudentId) {
                throw new DomainException('The approved student claimant no longer exists.');
            }

            if ($handedOverAt->isFuture()) {
                throw new DomainException('A completed physical handover cannot be recorded in the future.');
            }

            if ($handedOverAt->lt($item->custody_started_at)
                || $handedOverAt->lt($item->custody_changed_at)) {
                throw new DomainException('Physical handover cannot predate the current custody chain.');
            }

            $returnedStatus = ItemStateService::transition($item->status, ItemStatus::RETURNED);
            $handover = Handover::query()->create([
                'found_item_id' => $item->getKey(),
                'claim_id' => $approvedClaim->getKey(),
                'recipient_student_id' => $recipient->getKey(),
                'staff_user_id' => $staffUserId,
                'verification_method' => $verificationMethod,
                'verification_note' => $verificationNote,
                'handed_over_at' => $handedOverAt,
            ]);

            $item->custodyRecords()->create([
                'event_type' => CustodyEventType::HANDED_OVER,
                'actor_user_id' => $staffUserId,
                'from_custodian_user_id' => $item->current_custodian_user_id,
                'to_custodian_user_id' => null,
                'from_storage_location' => $item->current_storage_location,
                'to_storage_location' => null,
                'notes' => null,
                'occurred_at' => $handedOverAt,
            ]);

            $updated = DB::table('lost_found_items')
                ->where('id', $item->getKey())
                ->where('status', ItemStatus::IN_CUSTODY->value)
                ->where('approved_claim_id', $approvedClaim->getKey())
                ->where('current_custodian_user_id', $item->current_custodian_user_id)
                ->where('current_storage_location', $item->current_storage_location)
                ->where('custody_started_at', $item->getRawOriginal('custody_started_at'))
                ->where('custody_changed_at', $item->getRawOriginal('custody_changed_at'))
                ->update([
                    'status' => $returnedStatus->value,
                    'current_custodian_user_id' => null,
                    'current_storage_location' => null,
                    'custody_started_at' => null,
                    'custody_changed_at' => null,
                    'updated_at' => now(),
                ]);

            if ($updated !== 1) {
                throw new DomainException('The item state changed while physical handover was in progress.');
            }

            $item->refresh();
            $approvedClaim->refresh();

            if ($item->status !== ItemStatus::RETURNED
                || (int) $item->approved_claim_id !== $approvedClaim->getKey()
                || $approvedClaim->status !== ClaimStatus::APPROVED
                || $item->current_custodian_user_id !== null
                || $item->current_storage_location !== null
                || $item->custody_started_at !== null
                || $item->custody_changed_at !== null) {
                throw new DomainException('The final handover invariants were not established.');
            }

            return $handover->refresh();
        });
    }

    /** @return Collection<int, LostFoundClaim> */
    private function lockClaims(FoundItem $item): Collection
    {
        return LostFoundClaim::query()
            ->where('found_item_id', $item->getKey())
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }

    private function lockLatestCustodyRecord(FoundItem $item): ?CustodyRecord
    {
        return CustodyRecord::query()
            ->where('found_item_id', $item->getKey())
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();
    }

    private function assertCurrentCustody(FoundItem $item, ?CustodyRecord $latestCustody): void
    {
        if (! $latestCustody
            || $item->current_custodian_user_id === null
            || $item->current_storage_location === null
            || $item->custody_started_at === null
            || $item->custody_changed_at === null
            || (int) $latestCustody->to_custodian_user_id !== (int) $item->current_custodian_user_id
            || $latestCustody->to_storage_location !== $item->current_storage_location
            || ! $latestCustody->occurred_at->equalTo($item->custody_changed_at)) {
            throw new DomainException('Current custody projection does not match custody history.');
        }
    }

    private function lockAndValidateActiveStaff(int $staffUserId): void
    {
        $userModel = UserProxy::modelClass();
        $staff = $userModel::query()->lockForUpdate()->find($staffUserId);

        if (! $staff || ! (bool) $staff->status) {
            throw new DomainException('Physical handover requires an active staff user.');
        }
    }
}
