<?php

namespace Webkul\LostAndFound\Repositories;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;
use Webkul\Core\Eloquent\Repository;
use Webkul\LostAndFound\Contracts\LostFoundClaim;
use Webkul\LostAndFound\Enums\ClaimStatus;
use Webkul\LostAndFound\Enums\ItemStatus;
use Webkul\LostAndFound\Models\FoundItem;

class LostFoundClaimRepository extends Repository
{
    public function model(): string
    {
        return LostFoundClaim::class;
    }

    public function create(array $data)
    {
        $requestedStatus = $data['status'] ?? ClaimStatus::SUBMITTED;
        $requestedStatus = $requestedStatus instanceof ClaimStatus
            ? $requestedStatus
            : ClaimStatus::tryFrom((string) $requestedStatus);

        if ($requestedStatus !== ClaimStatus::SUBMITTED) {
            throw new InvalidArgumentException('New claims must begin in the submitted state.');
        }

        $data['status'] = ClaimStatus::SUBMITTED;
        $data['submitted_at'] ??= now();
        unset($data['withdrawn_at']);

        return DB::transaction(function () use ($data) {
            $item = FoundItem::query()->lockForUpdate()->findOrFail($data['found_item_id'] ?? null);

            if ($item->approved_claim_id !== null
                || ! in_array($item->status, [ItemStatus::REPORTED, ItemStatus::IN_CUSTODY], true)) {
                throw new InvalidArgumentException('This item cannot accept a new claim.');
            }

            return parent::create($data);
        });
    }

    public function update(array $data, $id, $attribute = 'id')
    {
        if (array_intersect_key($data, array_flip(['found_item_id', 'claimant_student_id', 'status', 'withdrawn_at']))) {
            throw new LogicException('Claim identity and workflow fields require a dedicated domain operation.');
        }

        return parent::update($data, $id, $attribute);
    }
}
