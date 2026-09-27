<?php

namespace Webkul\Admin\Http\Controllers\Events;

use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Webkul\Admin\DataGrids\Event\EventCategoryDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
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

        return view('admin::events.categories.index');
    }

    /**
     * Tree JSON for admin category pickers (same idea as Bagisto admin.catalog.categories.tree).
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

        return view('admin::events.categories.create', compact('parentCategories'));
    }

    public function store()
    {
        $this->validate(request(), [
            'name' => 'required|string',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer',
            'status' => 'nullable|boolean',
        ]);

        $data = request()->all();
        $data['status'] = request()->has('status') ? 1 : 0;
        $data['sort_order'] = request()->input('sort_order', 0);

        $this->eventCategoryRepository->create($data);

        session()->flash('success', trans('admin::app.event-categories.create-success'));

        return redirect()->route('admin.events.categories.index');
    }

    public function edit(int $id): View
    {
        $category = $this->eventCategoryRepository->findOrFail($id);
        $parentCategories = $this->eventCategoryRepository->all()->where('id', '!=', $id);

        return view('admin::events.categories.edit', compact('category', 'parentCategories'));
    }

    public function update(int $id)
    {
        $this->validate(request(), [
            'name' => 'required|string',
            'parent_id' => 'nullable|integer|exists:event_categories,id|not_in:'.$id,
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer',
            'status' => 'nullable|boolean',
        ]);

        $data = request()->all();
        $data['status'] = request()->has('status') ? 1 : 0;
        $data['sort_order'] = request()->input('sort_order', 0);
        $data['parent_id'] = request()->filled('parent_id') ? (int) request()->input('parent_id') : null;

        $this->eventCategoryRepository->update($data, $id);

        session()->flash('success', trans('admin::app.event-categories.update-success'));

        return redirect()->route('admin.events.categories.index');
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->eventCategoryRepository->delete($id);

            return new JsonResponse([
                'message' => trans('admin::app.event-categories.delete-success'),
            ], 200);
        } catch (\Exception $exception) {
            return new JsonResponse([
                'message' => trans('admin::app.event-categories.delete-failed'),
            ], 400);
        }
    }
}
