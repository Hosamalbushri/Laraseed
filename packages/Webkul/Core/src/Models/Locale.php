<?php

namespace Webkul\Core\Models;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use LogicException;
use Webkul\Core\Contracts\Locale as LocaleContract;
use Webkul\Core\Enums\LocaleDirection;

class Locale extends Model implements LocaleContract
{
    protected $table = 'locales';

    protected $fillable = [
        'code',
        'name',
        'direction',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'direction' => LocaleDirection::class,
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::updating(static function (Locale $locale): void {
            if ($locale->isDirty('code')) {
                throw new LogicException('Locale codes are immutable once persisted.');
            }

            if ($locale->isDirty('is_active')) {
                throw new LogicException('Locale activation must use the protected content-locale service.');
            }
        });

        static::deleting(static function (): never {
            throw new LogicException('Locale identities are retained; deactivate instead of deleting.');
        });
    }

    /**
     * Normalize language to lowercase and optional region to uppercase.
     */
    public static function normalizeCode(string $code): string
    {
        $code = str_replace('-', '_', trim($code));

        if (! preg_match('/\A([a-z]{2,3})(?:_([a-z]{2}|[0-9]{3}))?\z/iD', $code, $matches)) {
            throw new InvalidArgumentException('Locale code must be a two- or three-letter language with an optional two-letter or three-digit region.');
        }

        return strtolower($matches[1]).(isset($matches[2]) ? '_'.strtoupper($matches[2]) : '');
    }

    public function setCodeAttribute(string $code): void
    {
        $this->attributes['code'] = self::normalizeCode($code);
    }

    public function setNameAttribute(string $name): void
    {
        $name = trim($name);

        if ($name === '' || mb_strlen($name) > 120) {
            throw new InvalidArgumentException('Locale name must be non-empty and no longer than 120 characters.');
        }

        $this->attributes['name'] = $name;
    }

    public function setDirectionAttribute(LocaleDirection|string $direction): void
    {
        $this->attributes['direction'] = ($direction instanceof LocaleDirection
            ? $direction
            : LocaleDirection::from($direction))->value;
    }

    public function setSortOrderAttribute(int $sortOrder): void
    {
        if ($sortOrder < 0) {
            throw new InvalidArgumentException('Locale sort order cannot be negative.');
        }

        $this->attributes['sort_order'] = $sortOrder;
    }

    public function code(): string
    {
        return $this->code;
    }

    public function direction(): LocaleDirection
    {
        return $this->direction;
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }
}
