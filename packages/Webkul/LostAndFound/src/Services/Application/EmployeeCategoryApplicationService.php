<?php

namespace Webkul\LostAndFound\Services\Application;

use Webkul\LostAndFound\Models\LostFoundCategory;
use Webkul\LostAndFound\Repositories\LostFoundCategoryRepository;
use Webkul\User\Models\User;

class EmployeeCategoryApplicationService
{
    public function __construct(
        protected LostFoundCategoryRepository $categoryRepository
    ) {}

    public function createCategory(User $actor, array $data): LostFoundCategory
    {
        LostAndFoundAuthorization::authorizeUser($actor, 'lost_found.settings.categories');

        return $this->categoryRepository->create($data);
    }

    public function updateCategory(User $actor, int $id, array $data): LostFoundCategory
    {
        LostAndFoundAuthorization::authorizeUser($actor, 'lost_found.settings.categories');

        return $this->categoryRepository->update($data, $id);
    }
}
