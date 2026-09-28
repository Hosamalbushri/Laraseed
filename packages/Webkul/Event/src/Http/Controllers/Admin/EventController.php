<?php

namespace Webkul\Event\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Event\DataGrids\Admin\EventDataGrid;
use Webkul\Event\Http\Requests\Admin\SaveEventRequest;
use Webkul\Event\Repositories\EventRepository;
use Webkul\Event\Services\EventWriteService;

class EventController extends Controller
{
    public function __construct(
        protected EventRepository $eventRepository,
        protected EventWriteService $eventWriteService,
    ) {}

    public function index(): View|JsonResponse|BinaryFileResponse
    {
        if (request()->ajax()) {
            return datagrid(EventDataGrid::class)->process();
        }

        return view('event::admin.events.index');
    }

    public function create(): View
    {
        return view('event::admin.events.create');
    }

    public function store(SaveEventRequest $request)
    {
        $this->eventWriteService->create(
            $request->validated(),
            (array) $request->file('fields', []),
            (array) $request->input('images', []),
            (array) $request->file('images', []),
        );

        session()->flash('success', trans('event::app.events.create-success'));

        return redirect()->route('admin.events.index');
    }

    public function edit(int $id): View
    {
        $event = $this->eventRepository->with(['fields', 'categories', 'images'])->findOrFail($id);

        return view('event::admin.events.edit', compact('event'));
    }

    public function update(SaveEventRequest $request, int $id)
    {
        $this->eventWriteService->update(
            $id,
            $request->validated(),
            (array) $request->file('fields', []),
            (array) $request->input('images', []),
            (array) $request->file('images', []),
        );

        session()->flash('success', trans('event::app.events.update-success'));

        return redirect()->route('admin.events.index');
    }

    public function search(): JsonResponse
    {
        $results = [];

        foreach ($this->eventRepository->searchByTitle(
            (string) request()->input('query', ''),
            request()->filled('exclude') ? request()->integer('exclude') : null,
        ) as $event) {
            $results[] = [
                'id' => $event->id,
                'name' => $event->title,
            ];
        }

        return response()->json([
            'data' => $results,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->eventRepository->delete($id);

            return new JsonResponse([
                'message' => trans('event::app.events.delete-success'),
            ], 200);
        } catch (\Exception $exception) {
            return new JsonResponse([
                'message' => trans('event::app.events.delete-failed'),
            ], 400);
        }
    }
}
