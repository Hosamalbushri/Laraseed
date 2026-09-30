<?php

namespace Webkul\LostAndFound\Services;

use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Webkul\LostAndFound\Contracts\PublicLostAndFoundReadContract;
use Webkul\LostAndFound\DataTransferObjects\PublicCategoryData;
use Webkul\LostAndFound\DataTransferObjects\PublicFoundItemData;
use Webkul\LostAndFound\DataTransferObjects\PublicFoundItemSearchCriteria;
use Webkul\LostAndFound\DataTransferObjects\PublicFoundItemSearchResult;
use Webkul\LostAndFound\Enums\FoundItemImageVisibility;
use Webkul\LostAndFound\Enums\ItemStatus;
use Webkul\LostAndFound\Models\FoundItem;
use Webkul\LostAndFound\Models\LostFoundCategory;

class PublicLostAndFoundService implements PublicLostAndFoundReadContract
{
    /**
     * Retrieve recent public-safe found items for public presentation.
     *
     * @param  int  $limit  Maximum number of items to return (clamped between 1 and 24)
     * @return list<PublicFoundItemData>
     */
    public function getRecentPublicFoundItems(int $limit = 6): array
    {
        $clampedLimit = max(1, min($limit, 24));

        $items = FoundItem::query()
            ->select([
                'id',
                'public_reference',
                'category_id',
                'title',
                'public_description',
                'found_location',
                'found_at',
                'status',
            ])
            ->whereIn('status', [ItemStatus::REPORTED, ItemStatus::IN_CUSTODY])
            ->with([
                'category:id,code,is_active',
                'coverImage',
            ])
            ->orderByDesc('found_at')
            ->orderByDesc('id')
            ->limit($clampedLimit)
            ->get();

        return $items->map(fn (FoundItem $item): PublicFoundItemData => $this->mapToDto($item))->values()->all();
    }

    /**
     * Search and filter public-safe found items with bounded pagination.
     */
    public function searchPublicFoundItems(PublicFoundItemSearchCriteria $criteria): PublicFoundItemSearchResult
    {
        $query = FoundItem::query()
            ->select([
                'id',
                'public_reference',
                'category_id',
                'title',
                'public_description',
                'found_location',
                'found_at',
                'status',
            ])
            ->whereIn('status', [ItemStatus::REPORTED, ItemStatus::IN_CUSTODY]);

        // Filter by category if specified
        if ($criteria->category !== null) {
            $category = LostFoundCategory::query()
                ->where('code', $criteria->category)
                ->where('is_active', true)
                ->first();

            if (! $category) {
                // Return empty result when category is unknown or inactive
                return new PublicFoundItemSearchResult(
                    items: [],
                    total: 0,
                    perPage: $criteria->perPage,
                    currentPage: $criteria->page,
                    lastPage: 1,
                );
            }

            $query->where('category_id', $category->id);
        }

        // Search text across public fields only
        if ($criteria->query !== null) {
            $pattern = '%'.$this->escapeLike($criteria->query).'%';
            $referencePattern = '%'.$this->escapeLike(strtolower($criteria->query)).'%';

            $query->where(function ($sub) use ($pattern, $referencePattern): void {
                $sub->whereRaw("title LIKE ? ESCAPE '!'", [$pattern])
                    ->orWhereRaw("public_description LIKE ? ESCAPE '!'", [$pattern])
                    ->orWhereRaw("found_location LIKE ? ESCAPE '!'", [$pattern])
                    ->orWhereRaw("public_reference_key LIKE ? ESCAPE '!'", [$referencePattern]);
            });
        }

        $total = $query->count();
        $lastPage = max(1, (int) ceil($total / $criteria->perPage));
        $currentPage = min($criteria->page, $lastPage);
        $offset = ($currentPage - 1) * $criteria->perPage;

        $items = $query->with([
            'category:id,code,is_active',
            'coverImage',
        ])
            ->orderByDesc('found_at')
            ->orderByDesc('id')
            ->skip($offset)
            ->take($criteria->perPage)
            ->get();

        $dtoItems = $items->map(fn (FoundItem $item): PublicFoundItemData => $this->mapToDto($item))->values()->all();

        return new PublicFoundItemSearchResult(
            items: $dtoItems,
            total: $total,
            perPage: $criteria->perPage,
            currentPage: $currentPage,
            lastPage: $lastPage,
        );
    }

    /**
     * Find a single public-safe found item by its unique public reference.
     */
    public function findPublicFoundItemByReference(string $reference): ?PublicFoundItemData
    {
        try {
            $normalizedKey = PublicReference::normalize($reference);
        } catch (InvalidArgumentException) {
            return null;
        }

        $item = FoundItem::query()
            ->select([
                'id',
                'public_reference',
                'category_id',
                'title',
                'public_description',
                'found_location',
                'found_at',
                'status',
            ])
            ->where('public_reference_key', $normalizedKey)
            ->whereIn('status', [ItemStatus::REPORTED, ItemStatus::IN_CUSTODY])
            ->with([
                'category:id,code,is_active',
                'coverImage',
                'images' => function ($query): void {
                    $query->where('visibility', FoundItemImageVisibility::PUBLIC_SAFE->value)
                        ->orderBy('sort_order')
                        ->orderBy('id')
                        ->limit(6);
                },
            ])
            ->first();

        if (! $item) {
            return null;
        }

        return $this->mapToDto($item, loadAdditionalImages: true);
    }

    /**
     * Retrieve active categories for public search filtering.
     *
     * @return list<PublicCategoryData>
     */
    public function getPublicCategories(): array
    {
        return LostFoundCategory::query()
            ->select(['id', 'code'])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get()
            ->map(fn (LostFoundCategory $cat): PublicCategoryData => new PublicCategoryData(
                code: $cat->code,
                name: $cat->code,
            ))
            ->values()
            ->all();
    }

    /**
     * Map a FoundItem model to an immutable PublicFoundItemData DTO.
     */
    protected function mapToDto(FoundItem $item, bool $loadAdditionalImages = false): PublicFoundItemData
    {
        $cover = $item->coverImage;
        $imageUrl = null;
        $hasImage = false;

        if ($cover && $cover->visibility === FoundItemImageVisibility::PUBLIC_SAFE) {
            $hasImage = true;
            $imageUrl = Storage::disk('public')->url($cover->storage_key);
        }

        $categoryCode = null;
        if ($item->category && $item->category->is_active) {
            $categoryCode = $item->category->code;
        }

        $additionalImages = [];
        if ($loadAdditionalImages && $item->relationLoaded('images')) {
            foreach ($item->images as $img) {
                if ($img->visibility === FoundItemImageVisibility::PUBLIC_SAFE) {
                    $additionalImages[] = Storage::disk('public')->url($img->storage_key);
                }
            }
        }

        return new PublicFoundItemData(
            reference: $item->public_reference,
            title: $item->title,
            category: $categoryCode,
            foundLocation: $item->found_location,
            foundAt: $item->found_at,
            description: $item->public_description,
            imageUrl: $imageUrl,
            hasImage: $hasImage,
            additionalImages: $additionalImages,
        );
    }

    /**
     * Escape special SQL LIKE wildcards using ! as the escape character.
     */
    protected function escapeLike(string $value): string
    {
        return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $value);
    }
}
