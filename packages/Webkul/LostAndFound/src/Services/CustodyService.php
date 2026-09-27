<?php

namespace Webkul\LostAndFound\Services;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use DomainException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Webkul\LostAndFound\Enums\CustodyEventType;
use Webkul\LostAndFound\Enums\ItemStatus;
use Webkul\LostAndFound\Models\CustodyRecord;
use Webkul\LostAndFound\Models\FoundItem;
use Webkul\User\Models\UserProxy;

class CustodyService
{
    private const MAX_LOCATION_LENGTH = 255;

    public function receive(
        int $foundItemId,
        int $actorUserId,
        int $custodianUserId,
        string $storageLocation,
        ?DateTimeInterface $occurredAt = null,
        ?string $notes = null,
    ): FoundItem {
        $storageLocation = $this->normalizeLocation($storageLocation);
        $occurredAt = $this->normalizeTime($occurredAt);

        return DB::transaction(function () use (
            $foundItemId,
            $actorUserId,
            $custodianUserId,
            $storageLocation,
            $occurredAt,
            $notes,
        ): FoundItem {
            $item = $this->lockItem($foundItemId);

            if ($item->current_custodian_user_id !== null
                || $item->current_storage_location !== null
                || $item->custody_started_at !== null
                || $item->custody_changed_at !== null
                || $this->lockLatestRecord($item) !== null) {
                throw new DomainException('The item already has custody history or a current custody projection.');
            }

            $toStatus = ItemStateService::transition($item->status, ItemStatus::IN_CUSTODY);
            $this->lockAndValidateActiveUsers([$actorUserId, $custodianUserId]);

            $item->custodyRecords()->create([
                'event_type' => CustodyEventType::LOGGED,
                'actor_user_id' => $actorUserId,
                'from_custodian_user_id' => null,
                'to_custodian_user_id' => $custodianUserId,
                'from_storage_location' => null,
                'to_storage_location' => $storageLocation,
                'notes' => $notes,
                'occurred_at' => $occurredAt,
            ]);

            $updated = DB::table('lost_found_items')
                ->where('id', $item->getKey())
                ->where('status', $item->status->value)
                ->whereNull('current_custodian_user_id')
                ->whereNull('current_storage_location')
                ->whereNull('custody_started_at')
                ->whereNull('custody_changed_at')
                ->update([
                    'status' => $toStatus->value,
                    'current_custodian_user_id' => $custodianUserId,
                    'current_storage_location' => $storageLocation,
                    'custody_started_at' => $occurredAt,
                    'custody_changed_at' => $occurredAt,
                    'updated_at' => now(),
                ]);

            if ($updated !== 1) {
                throw new DomainException('The item custody state changed while receipt was in progress.');
            }

            $item->refresh();
            $this->assertProjectionMatchesHistory($item);

            return $item;
        });
    }

    public function transfer(
        int $foundItemId,
        int $actorUserId,
        int $expectedCustodianUserId,
        string $expectedStorageLocation,
        int $toCustodianUserId,
        string $toStorageLocation,
        ?DateTimeInterface $occurredAt = null,
        ?string $notes = null,
    ): FoundItem {
        if ($expectedCustodianUserId === $toCustodianUserId) {
            throw new InvalidArgumentException('A custody transfer must change the responsible custodian.');
        }

        return $this->changeCurrentCustody(
            CustodyEventType::TRANSFERRED,
            $foundItemId,
            $actorUserId,
            $expectedCustodianUserId,
            $expectedStorageLocation,
            $toCustodianUserId,
            $toStorageLocation,
            $occurredAt,
            $notes,
        );
    }

    public function moveStorage(
        int $foundItemId,
        int $actorUserId,
        int $expectedCustodianUserId,
        string $expectedStorageLocation,
        string $toStorageLocation,
        ?DateTimeInterface $occurredAt = null,
        ?string $notes = null,
    ): FoundItem {
        return $this->changeCurrentCustody(
            CustodyEventType::STORAGE_LOCATION_CHANGED,
            $foundItemId,
            $actorUserId,
            $expectedCustodianUserId,
            $expectedStorageLocation,
            $expectedCustodianUserId,
            $toStorageLocation,
            $occurredAt,
            $notes,
        );
    }

    private function changeCurrentCustody(
        CustodyEventType $eventType,
        int $foundItemId,
        int $actorUserId,
        int $expectedCustodianUserId,
        string $expectedStorageLocation,
        int $toCustodianUserId,
        string $toStorageLocation,
        ?DateTimeInterface $occurredAt,
        ?string $notes,
    ): FoundItem {
        $expectedStorageLocation = $this->normalizeLocation($expectedStorageLocation);
        $toStorageLocation = $this->normalizeLocation($toStorageLocation);
        $occurredAt = $this->normalizeTime($occurredAt);

        return DB::transaction(function () use (
            $eventType,
            $foundItemId,
            $actorUserId,
            $expectedCustodianUserId,
            $expectedStorageLocation,
            $toCustodianUserId,
            $toStorageLocation,
            $occurredAt,
            $notes,
        ): FoundItem {
            $item = $this->lockItem($foundItemId);

            if ($item->status !== ItemStatus::IN_CUSTODY) {
                throw new DomainException('Only an item currently in institutional custody may be moved.');
            }

            if ((int) $item->current_custodian_user_id !== $expectedCustodianUserId
                || $item->current_storage_location !== $expectedStorageLocation) {
                throw new DomainException('The expected custody source is stale.');
            }

            if ($expectedCustodianUserId === $toCustodianUserId
                && $expectedStorageLocation === $toStorageLocation) {
                throw new InvalidArgumentException('Custody operations must change the custodian or storage location.');
            }

            if ($eventType === CustodyEventType::STORAGE_LOCATION_CHANGED
                && $expectedCustodianUserId !== $toCustodianUserId) {
                throw new DomainException('A storage move cannot change the responsible custodian.');
            }

            $latestRecord = $this->lockLatestRecord($item);
            $this->assertProjectionMatchesHistory($item, $latestRecord);

            if ($occurredAt->lt($item->custody_changed_at)) {
                throw new DomainException('A custody event cannot occur before the current custody event.');
            }

            $this->lockAndValidateActiveUsers([$actorUserId, $toCustodianUserId]);

            $item->custodyRecords()->create([
                'event_type' => $eventType,
                'actor_user_id' => $actorUserId,
                'from_custodian_user_id' => $expectedCustodianUserId,
                'to_custodian_user_id' => $toCustodianUserId,
                'from_storage_location' => $expectedStorageLocation,
                'to_storage_location' => $toStorageLocation,
                'notes' => $notes,
                'occurred_at' => $occurredAt,
            ]);

            $updated = DB::table('lost_found_items')
                ->where('id', $item->getKey())
                ->where('status', ItemStatus::IN_CUSTODY->value)
                ->where('current_custodian_user_id', $expectedCustodianUserId)
                ->where('current_storage_location', $expectedStorageLocation)
                ->where('custody_changed_at', $item->getRawOriginal('custody_changed_at'))
                ->update([
                    'current_custodian_user_id' => $toCustodianUserId,
                    'current_storage_location' => $toStorageLocation,
                    'custody_changed_at' => $occurredAt,
                    'updated_at' => now(),
                ]);

            if ($updated !== 1) {
                throw new DomainException('The item custody state changed while the operation was in progress.');
            }

            $item->refresh();
            $this->assertProjectionMatchesHistory($item);

            return $item;
        });
    }

    private function lockItem(int $foundItemId): FoundItem
    {
        return FoundItem::query()->lockForUpdate()->findOrFail($foundItemId);
    }

    private function lockLatestRecord(FoundItem $item): ?CustodyRecord
    {
        return CustodyRecord::query()
            ->where('found_item_id', $item->getKey())
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();
    }

    /** @param array<int, int> $userIds */
    private function lockAndValidateActiveUsers(array $userIds): Collection
    {
        $userIds = array_values(array_unique($userIds));
        $userModel = UserProxy::modelClass();
        $users = $userModel::query()
            ->whereIn('id', $userIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get(['id', 'status']);

        if ($users->count() !== count($userIds)
            || $users->contains(static fn ($user): bool => ! (bool) $user->status)) {
            throw new DomainException('Custody actors and destination custodians must be active users.');
        }

        return $users;
    }

    private function assertProjectionMatchesHistory(
        FoundItem $item,
        ?CustodyRecord $latestRecord = null,
    ): void {
        $latestRecord ??= $this->lockLatestRecord($item);

        if (! $latestRecord
            || $item->current_custodian_user_id === null
            || $item->current_storage_location === null
            || $item->custody_started_at === null
            || $item->custody_changed_at === null
            || (int) $latestRecord->to_custodian_user_id !== (int) $item->current_custodian_user_id
            || $latestRecord->to_storage_location !== $item->current_storage_location
            || ! $latestRecord->occurred_at->equalTo($item->custody_changed_at)) {
            throw new DomainException('Current custody projection does not match custody history.');
        }
    }

    private function normalizeLocation(string $location): string
    {
        $location = trim($location);

        if ($location === '' || mb_strlen($location) > self::MAX_LOCATION_LENGTH) {
            throw new InvalidArgumentException('Storage location must contain between 1 and 255 characters.');
        }

        return $location;
    }

    private function normalizeTime(?DateTimeInterface $occurredAt): CarbonImmutable
    {
        return $occurredAt
            ? CarbonImmutable::instance($occurredAt)
            : CarbonImmutable::now();
    }
}
