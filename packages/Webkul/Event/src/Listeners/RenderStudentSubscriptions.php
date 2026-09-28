<?php

namespace Webkul\Event\Listeners;

use Webkul\Core\ViewRenderEventManager;
use Webkul\Event\Repositories\EventRepository;
use Webkul\Student\Models\Student;

class RenderStudentSubscriptions
{
    public function __construct(
        protected EventRepository $eventRepository
    ) {}

    public function handle(ViewRenderEventManager $manager): void
    {
        $student = $manager->getParam('student');

        if (! $student instanceof Student) {
            return;
        }

        $manager->addTemplate(view('event::admin.students.view.subscriptions', [
            'student' => $student,
            'events' => $this->eventRepository->availableForSubscription(),
            'subscribedEvents' => $this->eventRepository->subscribedByStudent((int) $student->getKey()),
        ])->render());
    }
}
