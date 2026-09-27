<?php

namespace Webkul\LostAndFound\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\LostAndFound\Contracts\LostFoundCategory as LostFoundCategoryContract;

class LostFoundCategory extends Model implements LostFoundCategoryContract
{
    protected $table = 'lost_found_categories';

    protected $fillable = [
        'code',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function foundItems()
    {
        return $this->hasMany(FoundItemProxy::modelClass(), 'category_id');
    }

    public function lostReports()
    {
        return $this->hasMany(LostReportProxy::modelClass(), 'category_id');
    }
}
