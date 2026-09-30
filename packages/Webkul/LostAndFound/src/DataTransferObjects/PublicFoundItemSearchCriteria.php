<?php

namespace Webkul\LostAndFound\DataTransferObjects;

readonly class PublicFoundItemSearchCriteria
{
    public ?string $query;

    public ?string $category;

    public int $page;

    public int $perPage;

    public function __construct(
        ?string $query = null,
        ?string $category = null,
        int $page = 1,
        int $perPage = 12,
    ) {
        $trimmedQuery = $query !== null ? trim($query) : null;
        $this->query = ($trimmedQuery !== '' && $trimmedQuery !== null) ? mb_substr($trimmedQuery, 0, 100) : null;

        $trimmedCategory = $category !== null ? trim($category) : null;
        $this->category = ($trimmedCategory !== '' && $trimmedCategory !== null) ? mb_substr($trimmedCategory, 0, 64) : null;

        $this->page = max(1, $page);
        $this->perPage = max(1, min($perPage, 36));
    }
}
