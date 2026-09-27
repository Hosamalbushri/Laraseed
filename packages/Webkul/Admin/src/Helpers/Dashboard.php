<?php

namespace Webkul\Admin\Helpers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Webkul\Event\Models\Event;
use Webkul\Student\Models\Student;

class Dashboard
{
    public function getEventsStudentsOverAllStats(): array
    {
        [$startDate, $endDate] = $this->getDateBounds();
        [$previousStart, $previousEnd] = $this->getPreviousDateBounds($startDate, $endDate);

        $metrics = [
            'total_events' => $this->getMetricProgress(
                fn () => Event::query(),
                $startDate,
                $endDate,
                $previousStart,
                $previousEnd
            ),
            'published_events' => $this->getMetricProgress(
                fn () => Event::query()->where('status', true),
                $startDate,
                $endDate,
                $previousStart,
                $previousEnd
            ),
            'currently_available_events' => $this->getMetricProgress(
                fn () => Event::query()->where('status', true)->where('event_end_date', '>=', now()),
                $startDate,
                $endDate,
                $previousStart,
                $previousEnd
            ),
            'ending_soon_events' => $this->getMetricProgress(
                fn () => Event::query()->whereBetween('event_end_date', [now(), now()->copy()->addDays(7)]),
                $startDate,
                $endDate,
                $previousStart,
                $previousEnd
            ),
            'total_students' => $this->getMetricProgress(
                fn () => Student::query(),
                $startDate,
                $endDate,
                $previousStart,
                $previousEnd
            ),
            'students_with_subscriptions' => $this->getMetricProgressFromTable(
                'event_student',
                'student_id',
                $startDate,
                $endDate,
                $previousStart,
                $previousEnd,
                true
            ),
            'total_subscriptions' => $this->getMetricProgressFromTable(
                'event_student',
                'id',
                $startDate,
                $endDate,
                $previousStart,
                $previousEnd,
                false
            ),
        ];

        return $metrics;
    }

    public function getStudentSubscriptionsOverTime(): array
    {
        [$startDate, $endDate] = $this->getDateBounds();

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

    public function getEventsStatusDistribution(): array
    {
        return [
            [
                'name' => trans('admin::app.dashboard.index.events-status-distribution.published'),
                'total' => Event::query()->where('status', true)->count(),
            ],
            [
                'name' => trans('admin::app.dashboard.index.events-status-distribution.unpublished'),
                'total' => Event::query()->where('status', false)->count(),
            ],
        ];
    }

    public function getTopSubscribedEvents(): array
    {
        [$startDate, $endDate] = $this->getDateBounds();

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
     * Get the start date.
     *
     * @return \Carbon\Carbon
     */
    public function getStartDate(): Carbon
    {
        $start = request()->query('start');

        if (! $start) {
            return now()->subDays(29)->startOfDay();
        }

        return Carbon::parse($start)->startOfDay();
    }

    /**
     * Get the end date.
     *
     * @return \Carbon\Carbon
     */
    public function getEndDate(): Carbon
    {
        $end = request()->query('end');

        if (! $end) {
            return now()->endOfDay();
        }

        return Carbon::parse($end)->endOfDay();
    }

    /**
     * Returns date range
     */
    public function getDateRange(): string
    {
        return $this->getStartDate()->format('d M').' - '.$this->getEndDate()->format('d M');
    }

    protected function getDateBounds(): array
    {
        $startDate = $this->getStartDate()->copy()->startOfDay();
        $endDate = $this->getEndDate()->copy()->endOfDay();

        if ($endDate->lt($startDate)) {
            [$startDate, $endDate] = [$endDate->copy()->startOfDay(), $startDate->copy()->endOfDay()];
        }

        return [$startDate, $endDate];
    }

    protected function getPreviousDateBounds(Carbon $startDate, Carbon $endDate): array
    {
        $periodDays = max(1, $startDate->diffInDays($endDate) + 1);

        $previousEnd = $startDate->copy()->subDay()->endOfDay();
        $previousStart = $previousEnd->copy()->subDays($periodDays - 1)->startOfDay();

        return [$previousStart, $previousEnd];
    }

    protected function getMetricProgress(
        callable $builderFactory,
        Carbon $startDate,
        Carbon $endDate,
        Carbon $previousStart,
        Carbon $previousEnd
    ): array {
        $currentBuilder = $builderFactory();
        $previousBuilder = $builderFactory();

        $current = $currentBuilder->whereBetween('created_at', [$startDate, $endDate])->count();
        $previous = $previousBuilder->whereBetween('created_at', [$previousStart, $previousEnd])->count();

        return [
            'current' => $current,
            'progress' => $this->calculateProgress($current, $previous),
        ];
    }

    protected function getMetricProgressFromTable(
        string $table,
        string $column,
        Carbon $startDate,
        Carbon $endDate,
        Carbon $previousStart,
        Carbon $previousEnd,
        bool $distinct = false
    ): array {
        $currentQuery = DB::table($table)->whereBetween('created_at', [$startDate, $endDate]);
        $previousQuery = DB::table($table)->whereBetween('created_at', [$previousStart, $previousEnd]);

        $current = $distinct ? $currentQuery->distinct()->count($column) : $currentQuery->count($column);
        $previous = $distinct ? $previousQuery->distinct()->count($column) : $previousQuery->count($column);

        return [
            'current' => $current,
            'progress' => $this->calculateProgress($current, $previous),
        ];
    }

    protected function calculateProgress(int|float $current, int|float $previous): float
    {
        if ((float) $previous === 0.0) {
            return (float) ($current > 0 ? 100 : 0);
        }

        return (($current - $previous) / $previous) * 100;
    }
}
