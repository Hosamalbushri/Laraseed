<?php

namespace Webkul\Event\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Webkul\Event\Contracts\EventCategory as EventCategoryContract;

class EventCategory extends Model implements EventCategoryContract
{
    protected $table = 'event_categories';

    protected $fillable = [
        'parent_id',
        'name',
        'description',
        'sort_order',
        'status',
    ];

    public function parent()
    {
        return $this->belongsTo(static::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(static::class, 'parent_id')->orderBy('sort_order')->orderBy('id');
    }

    public function events()
    {
        return $this->belongsToMany(EventProxy::modelClass(), 'event_event_category', 'event_category_id', 'event_id')
            ->withTimestamps();
    }

    /**
     * Build nested stdClass tree for admin v-tree-view (children must be objects for JS for-in).
     */
    public static function buildTreeObject(Collection $all, ?int $parentId = null): \stdClass
    {
        $obj = new \stdClass;

        $nodes = $all->where('parent_id', $parentId)
            ->sortBy([
                ['sort_order', 'asc'],
                ['id', 'asc'],
            ])
            ->values();

        foreach ($nodes as $category) {
            $key = 'n'.$category->id;
            $obj->{$key} = [
                'id' => $category->id,
                'key' => (string) $category->id,
                'value' => $category->id,
                'name' => $category->name,
                'children' => self::buildTreeObject($all, $category->id),
            ];
        }

        return $obj;
    }
}
