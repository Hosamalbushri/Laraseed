<?php

namespace Webkul\Core\Services;

use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use LogicException;
use Webkul\Core\Contracts\ContentLocaleManager;
use Webkul\Core\Enums\LocaleDirection;
use Webkul\Core\Models\Locale;
use Webkul\Core\Repositories\LocaleRepository;

/**
 * Authority for website/content languages; never reads or changes Admin UI locale.
 */
class ContentLocaleService implements ContentLocaleManager
{
    public function __construct(protected LocaleRepository $locales) {}

    public function activeContentLocales(): Collection
    {
        return $this->locales->activeOrdered();
    }

    public function primaryContentLocale(): Locale
    {
        $id = $this->setting()->primary_locale_id;

        $locale = Locale::query()->find($id);

        if (! $locale || ! $locale->is_active) {
            throw new LogicException('The primary content locale must be active and exist.');
        }

        return $locale;
    }

    public function allContentLocales(): Collection
    {
        return Locale::query()->orderBy('sort_order')->orderBy('code')->get();
    }

    public function findLocale(int $id): Locale
    {
        return $this->locales->findOrFail($id);
    }

    /**
     * Canonical validation rules for creating a content locale.
     *
     * @return array<string, mixed>
     */
    public function creationRules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:10',
                'regex:/\A[a-z]{2,3}(?:[-_](?:[a-z]{2}|[0-9]{3}))?\z/iD',
                Rule::unique('locales', 'code'),
            ],
            'name' => ['required', 'string', 'max:120'],
            'direction' => ['required', Rule::in(['ltr', 'rtl', LocaleDirection::LTR, LocaleDirection::RTL])],
            'sort_order' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * Canonical validation rules for updating content locale metadata.
     *
     * @return array<string, mixed>
     */
    public function updateRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'direction' => ['required', Rule::in(['ltr', 'rtl', LocaleDirection::LTR, LocaleDirection::RTL])],
            'sort_order' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * Validate and normalize raw input for locale creation.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function validateCreation(array $attributes): array
    {
        $data = $attributes;

        if (isset($data['code']) && is_string($data['code'])) {
            try {
                $data['code'] = Locale::normalizeCode($data['code']);
            } catch (InvalidArgumentException) {
                // Let the regex rule catch and surface a formatted validation error
            }
        }

        if (isset($data['direction']) && $data['direction'] instanceof LocaleDirection) {
            $data['direction'] = $data['direction']->value;
        }

        return validator($data, $this->creationRules())->validate();
    }

    /**
     * Validate raw input for locale metadata update.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function validateUpdate(array $attributes): array
    {
        $data = $attributes;

        if (isset($data['direction']) && $data['direction'] instanceof LocaleDirection) {
            $data['direction'] = $data['direction']->value;
        }

        return validator($data, $this->updateRules())->validate();
    }

    /**
     * Domain operation: validate, normalize, and create a new content locale.
     * Newly created locales always start inactive.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function createLocale(array $attributes): Locale
    {
        $validated = $this->validateCreation($attributes);
        $validated['is_active'] = false;

        return $this->create($validated);
    }

    /**
     * Persist a content locale with raw attributes.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Locale
    {
        $this->only($attributes, ['code', 'name', 'direction', 'sort_order', 'is_active']);

        return DB::transaction(function () use ($attributes): Locale {
            $this->setting(true);

            return Locale::query()->create($attributes);
        }, 3);
    }

    public function updateMetadata(int $id, array $attributes): Locale
    {
        $this->only($attributes, ['name', 'direction', 'sort_order']);

        $validated = $this->validateUpdate($attributes);

        return DB::transaction(function () use ($id, $validated): Locale {
            $this->setting(true);
            $locale = Locale::query()->lockForUpdate()->findOrFail($id);
            $locale->update($validated);

            return $locale->refresh();
        }, 3);
    }

    public function activate(int $id): Locale
    {
        return DB::transaction(function () use ($id): Locale {
            $this->setting(true);
            $locale = Locale::query()->lockForUpdate()->findOrFail($id);
            $this->setActive($locale, true);

            return $locale->refresh();
        }, 3);
    }

    public function deactivate(int $id): Locale
    {
        return DB::transaction(function () use ($id): Locale {
            $setting = $this->setting(true);
            $locale = Locale::query()->lockForUpdate()->findOrFail($id);

            if ((int) $setting->primary_locale_id === $id) {
                throw new DomainException('The primary content locale cannot be deactivated.');
            }

            if ($locale->is_active && Locale::query()->where('is_active', true)->count() <= 1) {
                throw new DomainException('The last active content locale cannot be deactivated.');
            }

            $this->setActive($locale, false);

            return $locale->refresh();
        }, 3);
    }

    public function changePrimary(int $id): Locale
    {
        return DB::transaction(function () use ($id): Locale {
            $this->setting(true);
            $locale = Locale::query()->lockForUpdate()->findOrFail($id);

            // A new primary becomes active in the same transaction as the switch.
            $this->setActive($locale, true);
            DB::table('content_locale_settings')->where('key', 'primary')->update([
                'primary_locale_id' => $id,
                'updated_at' => now(),
            ]);

            return $locale->refresh();
        }, 3);
    }

    protected function setting(bool $lock = false): object
    {
        $query = DB::table('content_locale_settings')->where('key', 'primary');

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->first() ?? throw new LogicException('Missing primary content locale authority.');
    }

    protected function setActive(Locale $locale, bool $active): void
    {
        if ($locale->is_active === $active) {
            return;
        }

        // The model disallows generic activation edits; only this locked path writes it.
        DB::table('locales')->where('id', $locale->id)->update([
            'is_active' => $active,
            'updated_at' => now(),
        ]);
    }

    protected function only(array $attributes, array $allowed): void
    {
        if (array_diff(array_keys($attributes), $allowed)) {
            throw new InvalidArgumentException('Unsupported content locale attributes.');
        }
    }
}
