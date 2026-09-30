<?php

namespace Webkul\LostAndFound\DataTransferObjects;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

readonly class PublicCategoryData implements Arrayable, JsonSerializable
{
    public function __construct(
        public string $code,
        public string $name,
    ) {}

    /**
     * @return array{code: string, name: string}
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
        ];
    }

    /**
     * @return array{code: string, name: string}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
