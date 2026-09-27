<?php

namespace Webkul\Admin\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Webkul\Admin\Helpers\Dashboard;

class DashboardController extends Controller
{
    /**
     * Request param functions
     *
     * @var array
     */
    protected $typeFunctions = [
        'events-students-over-all' => 'getEventsStudentsOverAllStats',
        'student-subscriptions-over-time' => 'getStudentSubscriptionsOverTime',
        'events-status-distribution' => 'getEventsStatusDistribution',
        'top-subscribed-events' => 'getTopSubscribedEvents',
    ];

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected Dashboard $dashboardHelper) {}

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

        if (! isset($this->typeFunctions[$type])) {
            return response()->json([
                'statistics' => [],
                'date_range' => $this->dashboardHelper->getDateRange(),
            ], 422);
        }

        $stats = $this->dashboardHelper->{$this->typeFunctions[$type]}();

        return response()->json([
            'statistics' => $stats,
            'date_range' => $this->dashboardHelper->getDateRange(),
        ]);
    }
}
