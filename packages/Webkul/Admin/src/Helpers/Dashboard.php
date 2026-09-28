<?php

namespace Webkul\Admin\Helpers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Dashboard
{
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

    public function getDateBounds(): array
    {
        $startDate = $this->getStartDate()->copy()->startOfDay();
        $endDate = $this->getEndDate()->copy()->endOfDay();

        if ($endDate->lt($startDate)) {
            [$startDate, $endDate] = [$endDate->copy()->startOfDay(), $startDate->copy()->endOfDay()];
        }

        return [$startDate, $endDate];
    }

    public function getPreviousDateBounds(Carbon $startDate, Carbon $endDate): array
    {
        $periodDays = max(1, $startDate->diffInDays($endDate) + 1);

        $previousEnd = $startDate->copy()->subDay()->endOfDay();
        $previousStart = $previousEnd->copy()->subDays($periodDays - 1)->startOfDay();

        return [$previousStart, $previousEnd];
    }

    public function getMetricProgress(
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

    public function getMetricProgressFromTable(
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
