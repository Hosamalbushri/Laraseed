<?php

namespace Webkul\Website\Integrations\LostAndFound\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Webkul\LostAndFound\Contracts\PublicLostAndFoundReadContract;
use Webkul\LostAndFound\DataTransferObjects\PublicFoundItemSearchCriteria;
use Webkul\Web\Contracts\SeoMetadataContract;

class LostAndFoundController extends Controller
{
    public function __construct(
        protected PublicLostAndFoundReadContract $reader,
        protected SeoMetadataContract $seoMetadata,
    ) {}

    /**
     * Display the public Lost & Found search and browsing directory.
     */
    public function index(Request $request): View
    {
        $query = $request->input('q');
        $category = $request->input('category');
        $rawPage = $request->input('page');

        $page = is_numeric($rawPage) ? max(1, (int) $rawPage) : 1;

        $criteria = new PublicFoundItemSearchCriteria(
            query: is_string($query) ? $query : null,
            category: is_string($category) ? $category : null,
            page: $page,
            perPage: 12,
        );

        $results = $this->reader->searchPublicFoundItems($criteria);
        $categories = $this->reader->getPublicCategories();

        $this->seoMetadata
            ->setTitle(trans('website::app.lost_found.search_title'))
            ->setDescription(trans('website::app.lost_found.search_description'))
            ->setCanonicalUrl(route('website.lost_found.index'));

        return view('website::lost-found.index', [
            'results'    => $results,
            'categories' => $categories,
            'criteria'   => $criteria,
        ]);
    }

    /**
     * Display public details for a single found item by its reference.
     */
    public function show(string $reference): View
    {
        $item = $this->reader->findPublicFoundItemByReference($reference);

        if (! $item) {
            abort(404);
        }

        $this->seoMetadata
            ->setTitle(trans('website::app.lost_found.item_title', ['title' => $item->title]))
            ->setDescription($item->description ?: trans('website::app.lost_found.search_description'))
            ->setCanonicalUrl(route('website.lost_found.show', ['reference' => $item->reference]));

        return view('website::lost-found.show', [
            'item' => $item,
        ]);
    }
}
