<?php

namespace Webkul\LostAndFound\Contracts;

use Webkul\LostAndFound\DataTransferObjects\PublicCategoryData;
use Webkul\LostAndFound\DataTransferObjects\PublicFoundItemData;
use Webkul\LostAndFound\DataTransferObjects\PublicFoundItemSearchCriteria;
use Webkul\LostAndFound\DataTransferObjects\PublicFoundItemSearchResult;

interface PublicLostAndFoundReadContract
{
    /**
     * Retrieve recent public-safe found items for public presentation.
     *
     * @param  int  $limit  Maximum number of items to return (clamped between 1 and 24)
     * @return list<PublicFoundItemData>
     */
    public function getRecentPublicFoundItems(int $limit = 6): array;

    /**
     * Search and filter public-safe found items with bounded pagination.
     */
    public function searchPublicFoundItems(PublicFoundItemSearchCriteria $criteria): PublicFoundItemSearchResult;

    /**
     * Find a single public-safe found item by its unique public reference.
     * Returns null if the item does not exist or is in a non-public state (draft, returned, disposed).
     */
    public function findPublicFoundItemByReference(string $reference): ?PublicFoundItemData;

    /**
     * Retrieve active categories for public search filtering.
     *
     * @return list<PublicCategoryData>
     */
    public function getPublicCategories(): array;
}
