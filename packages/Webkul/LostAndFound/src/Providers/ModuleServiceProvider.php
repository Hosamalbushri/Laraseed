<?php

namespace Webkul\LostAndFound\Providers;

use Webkul\Core\Providers\BaseModuleServiceProvider;
use Webkul\LostAndFound\Models\FoundItem;
use Webkul\LostAndFound\Models\FoundItemImage;
use Webkul\LostAndFound\Models\LostFoundCategory;
use Webkul\LostAndFound\Models\LostFoundClaim;
use Webkul\LostAndFound\Models\LostReport;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    /**
     * The models to be used by this module.
     *
     * @var array
     */
    protected $models = [
        LostFoundCategory::class,
        FoundItem::class,
        FoundItemImage::class,
        LostReport::class,
        LostFoundClaim::class,
    ];
}
