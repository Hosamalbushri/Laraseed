<?php

namespace Webkul\LostAndFound\DataTransferObjects;

use DateTimeInterface;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

readonly class PublicFoundItemData implements Arrayable, JsonSerializable
{
    public function __construct(
        public string $reference,
        public string $title,
        public ?string $category = null,
        public ?string $foundLocation = null,
        public ?DateTimeInterface $foundAt = null,
        public ?string $description = null,
        public ?string $imageUrl = null,
        public bool $hasImage = false,
        public array $additionalImages = [],
    ) {}

    /**
     * @return array{
     *     reference: string,
     *     title: string,
     *     category: string|null,
     *     found_location: string|null,
     *     found_at: string|null,
     *     description: string|null,
     *     image_url: string|null,
     *     has_image: bool,
     *     additional_images: list<string>
     * }
     */
    public function toArray(): array
    {
        return [
            'reference'         => $this->reference,
            'title'             => $this->title,
            'category'          => $this->category,
            'found_location'    => $this->foundLocation,
            'found_at'          => $this->foundAt?->format('Y-m-d H:i:s'),
            'description'       => $this->description,
            'image_url'         => $this->imageUrl,
            'has_image'         => $this->hasImage,
            'additional_images' => $this->additionalImages,
        ];
    }

    /**
     * @return array{
     *     reference: string,
     *     title: string,
     *     category: string|null,
     *     found_location: string|null,
     *     found_at: string|null,
     *     description: string|null,
     *     image_url: string|null,
     *     has_image: bool
     * }
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
