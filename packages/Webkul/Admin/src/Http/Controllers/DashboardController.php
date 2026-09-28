<?php

namespace Webkul\Admin\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Webkul\Admin\Helpers\Dashboard;
use Webkul\Admin\Helpers\DashboardStatsRegistry;

class DashboardController extends Controller
{
    /**
     * Request param functions
     *
     * @var array
     */
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected Dashboard $dashboardHelper,
        protected DashboardStatsRegistry $statsRegistry,
    ) {}

    /**
     * Display a listing of the resource.
     *
     * @return View
     */
    public function index()
    {
        return view('admin::dashboard.index')->with([
            'startDate' => $this->dashboardHelper->getStartDate(),
            'endDate' => $this->dashboardHelper->getEndDate(),
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function stats()
    {
        $type = request()->query('type');

        if (! is_string($type) || ! $this->statsRegistry->has($type)) {
            return response()->json([
                'statistics' => [],
                'date_range' => $this->dashboardHelper->getDateRange(),
            ], 422);
        }

        $stats = $this->statsRegistry->resolve($type);

        return response()->json([
            'statistics' => $stats,
            'date_range' => $this->dashboardHelper->getDateRange(),
        ]);
    }
}
