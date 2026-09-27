<?php

namespace Webkul\LostAndFound\Repositories;

use InvalidArgumentException;
use LogicException;
use Webkul\Core\Eloquent\Repository;
use Webkul\LostAndFound\Contracts\FoundItem;
use Webkul\LostAndFound\Enums\ItemStatus;

class FoundItemRepository extends Repository
{
    public function model(): string
    {
        return FoundItem::class;
    }

    public function create(array $data)
    {
        $this->assertNoCustodyProjection($data);
        $this->assertWaveOneStatus($data);
        unset($data['approved_claim_id']);

        return parent::create($data);
    }

    public function update(array $data, $id, $attribute = 'id')
    {
        $this->assertNoCustodyProjection($data);
        $this->assertWaveOneStatus($data);
        $this->assertNoWorkflowOrIdentityUpdate($data);

        return parent::update($data, $id, $attribute);
    }

    private function assertNoWorkflowOrIdentityUpdate(array $data): void
    {
        $workflowFields = [
            'approved_claim_id',
            'logged_by_user_id',
            'public_reference',
            'public_reference_key',
        ];

        if (array_intersect_key($data, array_flip($workflowFields))) {
            throw new LogicException('FoundItem workflow and identity fields require a dedicated domain operation.');
        }
    }

    private function assertNoCustodyProjection(array $data): void
    {
        $custodyFields = [
            'current_custodian_user_id',
            'current_storage_location',
            'custody_started_at',
            'custody_changed_at',
        ];

        if (array_intersect_key($data, array_flip($custodyFields))) {
            throw new LogicException('Current custody may only change through the custody service.');
        }
    }

    /**
     * CustodyService owns entry into custody; return and disposition remain later waves.
     */
    private function assertWaveOneStatus(array $data): void
    {
        if (! array_key_exists('status', $data)) {
            return;
        }

        $status = $data['status'] instanceof ItemStatus
            ? $data['status']
            : ItemStatus::tryFrom((string) $data['status']);

        if (! in_array($status, [ItemStatus::DRAFT, ItemStatus::REPORTED], true)) {
            throw new InvalidArgumentException('This Found Item status is not available in persistence wave one.');
        }
    }
}
