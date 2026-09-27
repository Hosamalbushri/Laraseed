<?php

namespace Webkul\LostAndFound\Services\Application;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Webkul\LostAndFound\Enums\FoundItemImageVisibility;
use Webkul\LostAndFound\Enums\ItemStatus;
use Webkul\LostAndFound\Models\FoundItem;
use Webkul\LostAndFound\Models\FoundItemImage;
use Webkul\LostAndFound\Repositories\FoundItemRepository;
use Webkul\LostAndFound\Services\FoundItemImageService;
use Webkul\User\Models\User;

class EmployeeItemApplicationService
{
    public function __construct(
        protected FoundItemRepository $itemRepository,
        protected FoundItemImageService $imageService
    ) {}

    public function createFoundItem(User $actor, array $data): FoundItem
    {
        LostAndFoundAuthorization::authorizeUser($actor, 'lost_found.items.create');

        $data['logged_by_user_id'] = $actor->id;
        $data['public_reference'] = $data['public_reference'] ?? 'FI-'.strtoupper(Str::random(10));
        $data['status'] = $data['status'] ?? ItemStatus::REPORTED->value;

        if (isset($data['description'])) {
            $data['public_description'] = $data['description'];
            unset($data['description']);
        }

        unset($data['approved_claim_id']);

        return $this->itemRepository->create($data);
    }

    public function updateFoundItem(User $actor, int $id, array $data): FoundItem
    {
        LostAndFoundAuthorization::authorizeUser($actor, 'lost_found.items.edit');

        if (isset($data['description'])) {
            $data['public_description'] = $data['description'];
            unset($data['description']);
        }

        return $this->itemRepository->update($data, $id);
    }

    public function addFoundItemImage(
        User $actor,
        int|FoundItem $item,
        FoundItemImageVisibility $visibility,
        UploadedFile|string $file
    ): FoundItemImage {
        LostAndFoundAuthorization::authorizeUser($actor, 'lost_found.items.edit');

        if (is_int($item)) {
            $item = $this->itemRepository->findOrFail($item);
        }

        return $this->imageService->addImage($item, $actor, $visibility, $file);
    }
}
