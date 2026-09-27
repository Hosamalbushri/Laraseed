<?php

namespace Webkul\Admin\Http\Controllers\Events;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Webkul\Admin\DataGrids\Event\EventDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Event\Models\Event;
use Webkul\Event\Repositories\EventRepository;

class EventController extends Controller
{
    public function __construct(
        protected EventRepository $eventRepository
    ) {}

    public function index(): View|JsonResponse|BinaryFileResponse
    {
        if (request()->ajax()) {
            return datagrid(EventDataGrid::class)->process();
        }

        return view('admin::events.index');
    }

    public function create(): View
    {
        return view('admin::events.create');
    }

    public function store()
    {
        $this->validate(request(), [
            'event_date' => 'required|date',
            'event_end_date' => 'required|date|after_or_equal:event_date',
            'organizer' => 'required|string|max:255',
            'title' => 'required|string',
            'category_ids' => 'required|array|min:1',
            'category_ids.*' => 'integer|exists:event_categories,id',
            'available_seats' => 'nullable|integer|min:0',
            'availability_use_seats' => 'nullable|boolean',
            'status' => 'nullable|boolean',
            'images' => 'nullable|array',
            'images.*' => 'nullable',
            'description' => 'nullable|string',
            'fields.*.name' => 'required_with:fields|string',
            'fields.*.type' => 'required_with:fields|string',
            'fields.*.value' => 'nullable',
        ]);

        $data = request()->all();

        $data = $this->mergeEventFormData($data);

        unset($data['image']);

        if (isset($data['fields'])) {
            foreach ($data['fields'] as $key => $field) {
                if (isset($field['type']) && $field['type'] === 'image') {
                    if (request()->hasFile("fields.{$key}.value")) {
                        $file = request()->file("fields.{$key}.value");
                        if (\is_array($file)) {
                            $file = $file[0];
                        }
                        $data['fields'][$key]['value'] = $file->store('event_fields', 'public');
                    }
                }
            }
        }

        $event = $this->eventRepository->create($data);
        $this->syncEventImages($event);

        session()->flash('success', trans('admin::app.events.create-success'));

        return redirect()->route('admin.events.index');
    }

    public function edit(int $id): View
    {
        $event = $this->eventRepository->with(['fields', 'categories', 'images'])->findOrFail($id);

        return view('admin::events.edit', compact('event'));
    }

    public function update(int $id)
    {
        $this->validate(request(), [
            'event_date' => 'required|date',
            'event_end_date' => 'required|date|after_or_equal:event_date',
            'organizer' => 'required|string|max:255',
            'title' => 'required|string',
            'category_ids' => 'required|array|min:1',
            'category_ids.*' => 'integer|exists:event_categories,id',
            'available_seats' => 'nullable|integer|min:0',
            'availability_use_seats' => 'nullable|boolean',
            'status' => 'nullable|boolean',
            'images' => 'nullable|array',
            'images.*' => 'nullable',
            'description' => 'nullable|string',
            'fields.*.name' => 'required_with:fields|string',
            'fields.*.type' => 'required_with:fields|string',
            'fields.*.value' => 'nullable',
        ]);

        $data = request()->all();
        //        return response()->json([
        //            'data' => $data,
        //        ]);

        $data = $this->mergeEventFormData($data);

        unset($data['image']);

        if (isset($data['fields'])) {
            foreach ($data['fields'] as $key => $field) {
                if (isset($field['type']) && $field['type'] === 'image') {
                    if (request()->hasFile("fields.{$key}.value")) {
                        $file = request()->file("fields.{$key}.value");
                        if (\is_array($file)) {
                            $file = $file[0];
                        }
                        $data['fields'][$key]['value'] = $file->store('event_fields', 'public');
                    } else {
                        // Restore old file path if no new file is uploaded
                        $data['fields'][$key]['value'] = $field['old_value'] ?? null;
                    }
                }
            }
        }

        $event = $this->eventRepository->update($data, $id);
        $this->syncEventImages($event);

        session()->flash('success', trans('admin::app.events.update-success'));

        return redirect()->route('admin.events.index');
    }

    public function search(): JsonResponse
    {
        $results = [];

        $query = $this->eventRepository->getModel()->where('title', 'like', '%'.request()->input('query').'%');

        if (request()->has('exclude')) {
            $query->where('id', '!=', request()->input('exclude'));
        }

        foreach ($query->get() as $event) {
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
                'message' => trans('admin::app.events.delete-success'),
            ], 200);
        } catch (\Exception $exception) {
            return new JsonResponse([
                'message' => trans('admin::app.events.delete-failed'),
            ], 400);
        }
    }

    /**
     * Normalize seats, end date, published flag, and availability rule toggles from the request.
     */
    protected function mergeEventFormData(array $data): array
    {
        $data['status'] = request()->boolean('status');
        $data['availability_use_seats'] = request()->boolean('availability_use_seats');
        // End-date availability is always enabled; end date itself is mandatory.
        $data['availability_use_end_date'] = true;

        $seats = request()->input('available_seats');
        if ($seats === '' || $seats === null) {
            $data['available_seats'] = null;
        } else {
            $data['available_seats'] = (int) $seats;
        }

        $end = request()->input('event_end_date');
        if ($end === '' || $end === null) {
            $data['event_end_date'] = null;
        } else {
            $data['event_end_date'] = $end;
        }

        return $data;
    }

    protected function syncEventImages(Event $event): void
    {
        $existingPaths = array_values(array_filter(array_map(
            'strval',
            array_keys((array) request()->input('images', []))
        )));

        $uploadedPaths = [];
        foreach ((array) request()->file('images', []) as $file) {
            if ($file instanceof UploadedFile) {
                $uploadedPaths[] = $file->store('events', 'public');
            }
        }

        $finalPaths = array_values(array_unique(array_filter(array_merge($existingPaths, $uploadedPaths))));

        if ($finalPaths === [] && $event->image) {
            // Preserve legacy single image if no gallery payload was provided.
            $finalPaths[] = (string) $event->image;
        }

        $current = $event->images()->pluck('path')->all();
        $toDelete = array_values(array_diff($current, $finalPaths));

        if ($toDelete !== []) {
            foreach ($toDelete as $path) {
                Storage::disk('public')->delete($path);
            }
        }

        $event->images()->delete();

        foreach ($finalPaths as $index => $path) {
            $event->images()->create([
                'path' => $path,
                'position' => $index,
            ]);
        }

        $event->image = $finalPaths[0] ?? null;
        $event->save();
    }
}
