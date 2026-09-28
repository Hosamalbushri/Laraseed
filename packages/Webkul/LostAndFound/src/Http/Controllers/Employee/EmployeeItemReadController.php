<?php

namespace Webkul\LostAndFound\Http\Controllers\Employee;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Webkul\LostAndFound\DataGrids\Employee\FoundItemDataGrid;
use Webkul\LostAndFound\Services\Application\LostAndFoundAuthorization;

class EmployeeItemReadController extends Controller
{
    public function index(): View|JsonResponse
    {
        LostAndFoundAuthorization::authorizeUser(auth('user')->user(), 'lost_found.items.view');

        if (request()->ajax()) {
            return datagrid(FoundItemDataGrid::class)->process();
        }

        return view('lost_found::employee.items.index');
    }
}
