<?php

namespace Webkul\Website\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use Webkul\Web\Contracts\SeoMetadataContract;
use Webkul\Website\Contracts\SiteDefinitionContract;

class AboutController extends Controller
{
    public function __construct(
        protected SeoMetadataContract $seoMetadata,
        protected SiteDefinitionContract $siteDefinition,
    ) {}

    /**
     * Display the site about page.
     */
    public function index(): View
    {
        $site = $this->siteDefinition->current();

        $this->seoMetadata
            ->setTitle(trans('website::app.about.title'))
            ->setDescription(trans('website::app.about.description'));

        return view('website::pages.about', [
            'siteDefinition' => $site,
        ]);
    }
}
