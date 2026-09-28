<?php

namespace Webkul\Event\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Webkul\Admin\Helpers\Dashboard;
use Webkul\Event\Models\Event;
use Webkul\Student\Models\Student;

class EventDashboardService
{
    /**
     * Get overall metric progress for Event package.
     */
    public function getOverallMetrics(
        Carbon $startDate,
        Carbon $endDate,
        Carbon $previousStart,
        Carbon $previousEnd,
        Dashboard $helper
    ): array {
        return [
            'total_events' => $helper->getMetricProgress(
                fn () => Event::query(),
                $startDate,
                $endDate,
                $previousStart,
                $previousEnd
            ),
            'published_events' => $helper->getMetricProgress(
                fn () => Event::query()->where('status', true),
                $startDate,
                $endDate,
                $previousStart,
                $previousEnd
            ),
            'currently_available_events' => $helper->getMetricProgress(
                fn () => Event::query()->where('status', true)->where('event_end_date', '>=', now()),
                $startDate,
                $endDate,
                $previousStart,
                $previousEnd
            ),
            'ending_soon_events' => $helper->getMetricProgress(
                fn () => Event::query()->whereBetween('event_end_date', [now(), now()->copy()->addDays(7)]),
                $startDate,
                $endDate,
                $previousStart,
                $previousEnd
            ),
            'total_students' => $helper->getMetricProgress(
                fn () => Student::query(),
                $startDate,
                $endDate,
                $previousStart,
                $previousEnd
            ),
            'students_with_subscriptions' => $helper->getMetricProgressFromTable(
                'event_student',
                'student_id',
                $startDate,
                $endDate,
                $previousStart,
                $previousEnd,
                true
            ),
            'total_subscriptions' => $helper->getMetricProgressFromTable(
                'event_student',
                'id',
                $startDate,
                $endDate,
                $previousStart,
                $previousEnd,
                false
            ),
        ];
    }

    /**
     * Get events status distribution.
     */
    public function getEventsStatusDistribution(): array
    {
        return [
            [
                'name' => trans('event::app.events.index.datagrid.published-yes'),
                'total' => Event::query()->where('status', true)->count(),
            ],
            [
                'name' => trans('event::app.events.index.datagrid.published-no'),
                'total' => Event::query()->where('status', false)->count(),
            ],
        ];
    }

    /**
     * Get top subscribed events.
     */
    public function getTopSubscribedEvents(Dashboard $helper): array
    {
        [$startDate, $endDate] = $helper->getDateBounds();

        return DB::table('event_student')
            ->join('events', 'events.id', '=', 'event_student.event_id')
            ->select([
                'event_student.event_id',
                'events.title as event_name',
                DB::raw('COUNT(event_student.id) as subscriptions_count'),
            ])
            ->whereBetween('event_student.created_at', [$startDate, $endDate])
            ->groupBy('event_student.event_id', 'events.title')
            ->orderByDesc('subscriptions_count')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'event_id' => (int) $row->event_id,
                'event_name' => $row->event_name,
                'subscriptions_count' => (int) $row->subscriptions_count,
            ])
            ->toArray();
    }

    /**
     * Get student subscriptions over time.
     */
    public function getStudentSubscriptionsOverTime(Dashboard $helper): array
    {
        [$startDate, $endDate] = $helper->getDateBounds();

        $records = DB::table('event_student')
            ->selectRaw('DATE(created_at) as day, COUNT(*) as count')
            ->whereBetween('created_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $overTime = [];
        $cursor = $startDate->copy();

        while ($cursor->lte($endDate)) {
            $day = $cursor->toDateString();

            $overTime[] = [
                'label' => $cursor->format('d M'),
                'count' => (int) ($records[$day]->count ?? 0),
            ];

            $cursor->addDay();
        }

        return [
            'all' => [
                'over_time' => $overTime,
            ],
        ];
    }
}
