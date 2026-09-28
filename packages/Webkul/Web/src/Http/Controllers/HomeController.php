<?php

namespace Webkul\Web\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use Webkul\Web\Contracts\SectionRegistryContract;
use Webkul\Web\Contracts\SeoMetadataContract;

class HomeController extends Controller
{
    public function __construct(
        protected SectionRegistryContract $sectionRegistry,
        protected SeoMetadataContract $seoMetadata,
    ) {}

    /**
     * Display the generic web landing page.
     */
    public function index(): View
    {
        $this->seoMetadata
            ->setTitle(trans('web::app.home.title'))
            ->setDescription(trans('web::app.home.description'));

        $sections = $this->sectionRegistry->getSections('home');

        return view('web::home.index', [
            'sections' => $sections,
        ]);
    }
}
