<?php

namespace Webkul\Event\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Event\DataGrids\Admin\EventCategoryDataGrid;
use Webkul\Event\Http\Requests\Admin\StoreEventCategoryRequest;
use Webkul\Event\Http\Requests\Admin\UpdateEventCategoryRequest;
use Webkul\Event\Models\EventCategory;
use Webkul\Event\Repositories\EventCategoryRepository;

class EventCategoryController extends Controller
{
    public function __construct(protected EventCategoryRepository $eventCategoryRepository) {}

    public function index(): View|JsonResponse
    {
        if (request()->ajax()) {
            return datagrid(EventCategoryDataGrid::class)->process();
        }

        return view('event::admin.events.categories.index');
    }

    /**
     * Tree JSON for admin category pickers.
     */
    public function tree(): JsonResponse
    {
        $categories = $this->eventCategoryRepository->all();

        return response()->json([
            'data' => EventCategory::buildTreeObject($categories),
        ]);
    }

    public function create(): View
    {
        $parentCategories = $this->eventCategoryRepository->all();

        return view('event::admin.events.categories.create', compact('parentCategories'));
    }

    public function store(StoreEventCategoryRequest $request)
    {
        $this->eventCategoryRepository->create($request->validated());

        session()->flash('success', trans('event::app.event-categories.create-success'));

        return redirect()->route('admin.events.categories.index');
    }

    public function edit(int $id): View
    {
        $category = $this->eventCategoryRepository->findOrFail($id);
        $parentCategories = $this->eventCategoryRepository->all()->where('id', '!=', $id);

        return view('event::admin.events.categories.edit', compact('category', 'parentCategories'));
    }

    public function update(UpdateEventCategoryRequest $request, int $id)
    {
        $this->eventCategoryRepository->update($request->validated(), $id);

        session()->flash('success', trans('event::app.event-categories.update-success'));

        return redirect()->route('admin.events.categories.index');
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->eventCategoryRepository->delete($id);

            return new JsonResponse([
                'message' => trans('event::app.event-categories.delete-success'),
            ], 200);
        } catch (\Exception $exception) {
            return new JsonResponse([
                'message' => trans('event::app.event-categories.delete-failed'),
            ], 400);
        }
    }
}
