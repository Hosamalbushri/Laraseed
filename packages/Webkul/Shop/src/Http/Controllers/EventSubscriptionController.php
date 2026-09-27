<?php

namespace Webkul\Shop\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Webkul\Event\Services\EventSubscriptionService;
use Webkul\Event\Services\Exceptions\SubscriptionFailedException;

class EventSubscriptionController extends Controller
{
    use Concerns\ResolvesStudentSubscribedEventIds;

    public function __construct(
        protected EventSubscriptionService $eventSubscriptionService
    ) {}

    public function store(int $id): JsonResponse
    {
        $student = auth('student')->user();

        try {
            $result = $this->eventSubscriptionService->subscribe($id, (int) $student->id);
        } catch (SubscriptionFailedException $e) {
            return new JsonResponse([
                'message' => $e->getMessage(),
            ], 422);
        }

        $message = $result['already_registered']
            ? __('shop::app.events.subscribe.already')
            : __('shop::app.events.subscribe.success');

        return new JsonResponse([
            'message' => $message,
            'already_registered' => $result['already_registered'],
            'available_seats' => $result['available_seats'],
            'availability_use_seats' => $result['availability_use_seats'],
            'subscribed_event_ids' => $this->studentSubscribedEventIds(),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $student = auth('student')->user();

        try {
            $this->eventSubscriptionService->unsubscribe($id, (int) $student->id);
        } catch (SubscriptionFailedException $e) {
            return new JsonResponse([
                'message' => $e->getMessage(),
            ], 422);
        }

        return new JsonResponse([
            'message' => __('shop::app.events.unsubscribe.success'),
            'subscribed_event_ids' => $this->studentSubscribedEventIds(),
        ]);
    }
}
