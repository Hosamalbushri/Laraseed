<?php

namespace Webkul\Event\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Event\Http\Requests\Admin\StoreStudentEventSubscriptionRequest;
use Webkul\Event\Repositories\EventRepository;
use Webkul\Event\Services\EventSubscriptionService;
use Webkul\Student\Repositories\StudentRepository;

class EventSubscriptionController extends Controller
{
    public function __construct(
        protected EventSubscriptionService $subscriptionService,
        protected EventRepository $eventRepository,
        protected StudentRepository $studentRepository,
    ) {}

    public function store(StoreStudentEventSubscriptionRequest $request, int $id): RedirectResponse
    {
        $data = $request->validated();

        $student = $this->studentRepository->findOrFail($id);
        $event = $this->eventRepository->findOrFail((int) $data['event_id']);

        if (! $event->status || ! $event->isCurrentlyAvailable()) {
            session()->flash('error', trans('event::app.students.subscriptions.event-unavailable'));

            return redirect()->route('admin.students.view', $id);
        }

        if (! $this->subscriptionService->subscribeForAdmin((int) $event->id, (int) $student->id)) {
            session()->flash('info', trans('event::app.students.subscriptions.already-exists'));

            return redirect()->route('admin.students.view', $id);
        }

        session()->flash('success', trans('event::app.students.subscriptions.create-success'));

        return redirect()->route('admin.students.view', $id);
    }

    public function destroy(int $id, int $eventId): RedirectResponse
    {
        $student = $this->studentRepository->findOrFail($id);

        if (! $this->subscriptionService->unsubscribeForAdmin($eventId, (int) $student->id)) {
            session()->flash('info', trans('event::app.students.subscriptions.not-found'));

            return redirect()->route('admin.students.view', $id);
        }

        session()->flash('success', trans('event::app.students.subscriptions.delete-success'));

        return redirect()->route('admin.students.view', $id);
    }
}
