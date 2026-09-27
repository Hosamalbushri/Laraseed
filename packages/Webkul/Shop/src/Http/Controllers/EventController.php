<?php

namespace Webkul\Shop\Http\Controllers;

use Illuminate\Contracts\View\View;
use Webkul\Event\Repositories\EventCategoryRepository;
use Webkul\Event\Repositories\EventRepository;

class EventController extends Controller
{
    use Concerns\ResolvesStudentSubscribedEventIds;

    public function __construct(
        protected EventRepository $eventRepository,
        protected EventCategoryRepository $eventCategoryRepository
    ) {}

    public function index(): View
    {
        $filters = [
            'query' => trim((string) request()->query('query', '')),
            'category' => (int) request()->query('category', 0),
            'date_from' => trim((string) request()->query('date_from', '')),
            'date_to' => trim((string) request()->query('date_to', '')),
            'availability' => trim((string) request()->query('availability', '')),
            'sort' => trim((string) request()->query('sort', 'latest')),
        ];

        $query = $this->eventRepository->getModel()
            ->newQuery()
            ->where('status', true)
            ->with('categories')
            ->withCount('subscribers');

        if ($filters['query'] !== '') {
            $like = '%'.addcslashes($filters['query'], '%_\\').'%';
            $query->where('title', 'like', $like);
        }

        if ($filters['category'] > 0) {
            $query->whereHas('categories', function ($q) use ($filters) {
                $q->where('event_categories.id', $filters['category']);
            });
        }

        if ($filters['date_from'] !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['date_from'])) {
            $query->whereDate('event_date', '>=', $filters['date_from']);
        }

        if ($filters['date_to'] !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['date_to'])) {
            $query->whereDate('event_date', '<=', $filters['date_to']);
        }

        if ($filters['availability'] === 'open') {
            $query->where(function ($q) {
                $q->where('availability_use_seats', false)
                    ->orWhereNull('available_seats')
                    ->orWhere('available_seats', '>', 0);
            })->where(function ($q) {
                $q->where('availability_use_end_date', false)
                    ->orWhere(function ($q2) {
                        $q2->whereNotNull('event_end_date')
                            ->whereDate('event_end_date', '>=', now()->toDateString());
                    });
            });
        } elseif ($filters['availability'] === 'sold_out') {
            $query->where('availability_use_seats', true)
                ->whereNotNull('available_seats')
                ->where('available_seats', '<=', 0);
        } elseif ($filters['availability'] === 'ended') {
            $query->where('availability_use_end_date', true)
                ->whereNotNull('event_end_date')
                ->whereDate('event_end_date', '<', now()->toDateString());
        }

        if ($filters['sort'] === 'oldest') {
            $query->orderBy('event_date')->orderBy('id');
        } elseif ($filters['sort'] === 'title_asc') {
            $query->orderBy('title')->orderByDesc('id');
        } elseif ($filters['sort'] === 'title_desc') {
            $query->orderByDesc('title')->orderByDesc('id');
        } elseif ($filters['sort'] === 'most_subscribed') {
            $query->orderByDesc('subscribers_count')->orderByDesc('id');
        } else {
            $query->orderByDesc('id');
        }

        $eventsPerPage = max(1, min(48, (int) (
            core()->getConfigData('general.store.events_page.per_page')
            ?: core()->getConfigData('general.design.events_page.per_page')
            ?: 12
        )));

        $events = $query->paginate($eventsPerPage)->withQueryString();
        $categories = $this->eventCategoryRepository->getModel()
            ->newQuery()
            ->where('status', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        $pageHeading = trim((string) (
            core()->getConfigData('general.store.events_page.heading')
            ?: core()->getConfigData('general.design.events_page.heading')
        ));
        $pageDescription = trim((string) (
            core()->getConfigData('general.store.events_page.description')
            ?: core()->getConfigData('general.design.events_page.description')
        ));

        return view('shop::events.index', [
            'events' => $events,
            'categories' => $categories,
            'filters' => $filters,
            'pageHeading' => $pageHeading !== '' ? $pageHeading : __('shop::app.events.index.heading'),
            'pageDescription' => $pageDescription !== '' ? $pageDescription : __('shop::app.events.index.subheading'),
            'subscribedEventIds' => $this->studentSubscribedEventIds(),
        ]);
    }

    public function show(int $id): View
    {
        $event = $this->eventRepository->getModel()
            ->newQuery()
            ->where('status', true)
            ->with([
                'categories',
                'images',
                'fields',
            ])
            ->whereKey($id)
            ->firstOrFail();

        $event->loadMissing([
            'categories',
            'images',
            'fields',
        ]);

        $event->loadCount([
            'subscribers',
        ]);

        // Suggested events: same idea/category when possible, otherwise latest.
        $categoryIds = $event->categories?->pluck('id')->filter()->values()->all() ?? [];
        $suggestedEventsQuery = $this->eventRepository->getModel()
            ->newQuery()
            ->where('status', true)
            ->whereKeyNot($event->id)
            ->with([
                'categories',
                'images',
            ]);

        if (! empty($categoryIds)) {
            $suggestedEventsQuery->whereHas('categories', function ($q) use ($categoryIds) {
                $q->whereIn('event_categories.id', $categoryIds);
            });
        }

        $suggestedEvents = $suggestedEventsQuery
            ->orderByDesc('id')
            ->limit(4)
            ->get();

        $subscribedEventIds = $this->studentSubscribedEventIds();

        return view('shop::events.show', [
            'event' => $event,
            'isSubscribedToCurrentEvent' => in_array((int) $event->id, $subscribedEventIds, true),
            'subscribedEventIds' => $subscribedEventIds,
            'suggestedEvents' => $suggestedEvents,
        ]);
    }
}
