<?php

namespace Webkul\LostAndFound\DataTransferObjects;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

readonly class PublicFoundItemSearchResult implements Arrayable, JsonSerializable
{
    /**
     * @param  list<PublicFoundItemData>  $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $perPage,
        public int $currentPage,
        public int $lastPage,
    ) {}

    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    public function isNotEmpty(): bool
    {
        return ! empty($this->items);
    }

    public function hasPages(): bool
    {
        return $this->lastPage > 1;
    }

    public function hasMorePages(): bool
    {
        return $this->currentPage < $this->lastPage;
    }

    public function previousPage(): ?int
    {
        return $this->currentPage > 1 ? $this->currentPage - 1 : null;
    }

    public function nextPage(): ?int
    {
        return $this->currentPage < $this->lastPage ? $this->currentPage + 1 : null;
    }

    /**
     * @return array{
     *     items: list<array<string, mixed>>,
     *     total: int,
     *     per_page: int,
     *     current_page: int,
     *     last_page: int
     * }
     */
    public function toArray(): array
    {
        return [
            'items'        => array_map(fn (PublicFoundItemData $item): array => $item->toArray(), $this->items),
            'total'        => $this->total,
            'per_page'     => $this->perPage,
            'current_page' => $this->currentPage,
            'last_page'    => $this->lastPage,
        ];
    }

    /**
     * @return array{
     *     items: list<array<string, mixed>>,
     *     total: int,
     *     per_page: int,
     *     current_page: int,
     *     last_page: int
     * }
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
