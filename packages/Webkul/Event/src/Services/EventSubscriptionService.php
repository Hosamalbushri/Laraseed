<?php

namespace Webkul\Event\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Webkul\Event\Models\Event;
use Webkul\Event\Services\Exceptions\SubscriptionFailedException;

class EventSubscriptionService
{
    /**
     * Register a student for an event with row lock, seat check, and optional seat decrement.
     *
     * @return array{already_registered: bool, available_seats: int|null, availability_use_seats: bool}
     *
     * @throws SubscriptionFailedException
     */
    public function subscribe(int $eventId, int $studentId): array
    {
        return DB::transaction(function () use ($eventId, $studentId) {
            /** @var Event|null $event */
            $event = Event::query()->whereKey($eventId)->lockForUpdate()->first();

            if (! $event) {
                throw new SubscriptionFailedException(__('shop::app.events.subscribe.event-not-found'));
            }

            if (! $event->status) {
                throw new SubscriptionFailedException(__('shop::app.events.subscribe.event-unavailable'));
            }

            if (! $event->isCurrentlyAvailable()) {
                throw new SubscriptionFailedException(__('shop::app.events.subscribe.not-available'));
            }

            $already = $event->subscribers()->where('students.id', $studentId)->exists();

            if ($already) {
                return [
                    'already_registered' => true,
                    'available_seats' => $event->available_seats,
                    'availability_use_seats' => (bool) $event->availability_use_seats,
                ];
            }

            if ($event->availability_use_seats && $event->available_seats !== null) {
                if ((int) $event->available_seats <= 0) {
                    throw new SubscriptionFailedException(__('shop::app.events.subscribe.no-seats'));
                }

                $event->available_seats = (int) $event->available_seats - 1;
                $event->save();
            }

            $event->subscribers()->attach($studentId);

            return [
                'already_registered' => false,
                'available_seats' => $event->available_seats,
                'availability_use_seats' => (bool) $event->availability_use_seats,
            ];
        });
    }

    /**
     * Remove a student's registration. Allowed only while the event has not ended
     * (same rule as storefront "ended" badge: after end date, cancellation is blocked).
     * Restores one seat when seat tracking is enabled.
     *
     * @throws SubscriptionFailedException
     */
    public function unsubscribe(int $eventId, int $studentId): void
    {
        DB::transaction(function () use ($eventId, $studentId) {
            /** @var Event|null $event */
            $event = Event::query()->whereKey($eventId)->lockForUpdate()->first();

            if (! $event) {
                throw new SubscriptionFailedException(__('shop::app.events.subscribe.event-not-found'));
            }

            if ($event->event_end_date
                && Carbon::today()->startOfDay()->gt(
                    Carbon::parse($event->event_end_date)->startOfDay()
                )
            ) {
                throw new SubscriptionFailedException(__('shop::app.events.unsubscribe.ended'));
            }

            $subscribed = $event->subscribers()->where('students.id', $studentId)->exists();

            if (! $subscribed) {
                throw new SubscriptionFailedException(__('shop::app.events.unsubscribe.not-subscribed'));
            }

            $event->subscribers()->detach($studentId);

            if ($event->availability_use_seats && $event->available_seats !== null) {
                $event->available_seats = (int) $event->available_seats + 1;
                $event->save();
            }
        });
    }
}
