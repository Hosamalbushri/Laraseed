<?php

namespace Webkul\Core\Repositories;

use Illuminate\Support\Collection;
use LogicException;
use Webkul\Core\Contracts\Locale as LocaleContract;
use Webkul\Core\Eloquent\Repository;
use Webkul\Core\Enums\LocaleDirection;
use Webkul\Core\Models\Locale;

class LocaleRepository extends Repository
{
    public function model(): string
    {
        return LocaleContract::class;
    }

    public function findByCode(string $code): ?LocaleContract
    {
        return $this->model->newQuery()
            ->where('code', Locale::normalizeCode($code))
            ->first();
    }

    public function activeOrdered(): Collection
    {
        return $this->model->newQuery()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get();
    }

    public function isActiveCode(string $code): bool
    {
        return $this->model->newQuery()
            ->where('code', Locale::normalizeCode($code))
            ->where('is_active', true)
            ->exists();
    }

    public function directionFor(string $code): ?LocaleDirection
    {
        return $this->findByCode($code)?->direction();
    }

    public function delete($id)
    {
        throw new LogicException('Locale identities are retained; deactivate instead of deleting.');
    }

    public function update(array $attributes, $id)
    {
        if (array_key_exists('code', $attributes) || array_key_exists('is_active', $attributes)) {
            throw new LogicException('Locale identity and activation require their dedicated operations.');
        }

        return parent::update($attributes, $id);
    }
}
