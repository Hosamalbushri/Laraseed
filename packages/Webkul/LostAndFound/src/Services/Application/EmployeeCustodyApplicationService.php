<?php

namespace Webkul\LostAndFound\Services\Application;

use Webkul\LostAndFound\Models\FoundItem;
use Webkul\LostAndFound\Services\CustodyService;
use Webkul\User\Models\User;

class EmployeeCustodyApplicationService
{
    public function __construct(
        protected CustodyService $custodyService
    ) {}

    public function receiveItem(User $actor, FoundItem $item, string $storageLocation, ?string $notes = null): FoundItem
    {
        LostAndFoundAuthorization::authorizeUser($actor, 'lost_found.custody.manage');

        return $this->custodyService->receive($item->id, $actor->id, $actor->id, $storageLocation, null, $notes);
    }

    public function transferItem(
        User $actor,
        FoundItem $item,
        User $newCustodian,
        string $storageLocation,
        ?string $notes = null
    ): FoundItem {
        LostAndFoundAuthorization::authorizeUser($actor, 'lost_found.custody.manage');

        return $this->custodyService->transfer($item->id, $actor->id, $newCustodian->id, $storageLocation, null, $notes);
    }

    public function moveStorage(User $actor, FoundItem $item, string $newStorageLocation, ?string $notes = null): FoundItem
    {
        LostAndFoundAuthorization::authorizeUser($actor, 'lost_found.custody.manage');

        return $this->custodyService->moveStorage($item->id, $actor->id, $newStorageLocation, null, $notes);
    }
}
