<?php

namespace Webkul\Shop\Http\Controllers\Concerns;

trait ResolvesStudentSubscribedEventIds
{
    /**
     * @return list<int>
     */
    protected function studentSubscribedEventIds(): array
    {
        if (! auth('student')->check()) {
            return [];
        }

        return auth('student')->user()
            ->subscribedEvents()
            ->pluck('events.id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}
