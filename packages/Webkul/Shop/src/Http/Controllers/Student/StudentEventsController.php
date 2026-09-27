<?php

namespace Webkul\Shop\Http\Controllers\Student;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StudentEventsController extends Controller
{
    public function index(): View
    {
        $student = Auth::guard('student')->user();

        $events = $student->subscribedEvents()
            ->with('categories')
            ->withCount('subscribers')
            ->orderByDesc('events.id')
            ->paginate(12);

        $subscribedEventIds = $events->getCollection()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        return view('shop::student.events.index', [
            'events' => $events,
            'subscribedEventIds' => $subscribedEventIds,
        ]);
    }
}
