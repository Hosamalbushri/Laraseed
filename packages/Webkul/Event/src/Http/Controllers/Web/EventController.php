<?php

namespace Webkul\Event\Http\Controllers\Web;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Webkul\Event\Repositories\EventRepository;
use Webkul\Web\Contracts\SeoMetadataContract;

class EventController extends Controller
{
    public function __construct(
        protected EventRepository $eventRepository,
        protected SeoMetadataContract $seoMetadata,
    ) {}

    public function index(): View
    {
        $this->seoMetadata
            ->setTitle(trans('event::web.index.title'))
            ->setDescription(trans('event::web.index.description'))
            ->setCanonicalUrl(route('event.web.index'));

        return view('event::web.index', [
            'events' => $this->eventRepository->paginatePublic(),
        ]);
    }

    public function show(int $event): View
    {
        $publicEvent = $this->eventRepository->findPublicOrFail($event);

        $this->seoMetadata
            ->setTitle((string) $publicEvent->title)
            ->setDescription(Str::limit((string) $publicEvent->description, 160))
            ->setCanonicalUrl(route('event.web.show', $publicEvent->getKey()));

        return view('event::web.show', [
            'event' => $publicEvent,
        ]);
    }
}
