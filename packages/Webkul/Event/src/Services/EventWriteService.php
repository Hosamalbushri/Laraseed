<?php

namespace Webkul\Event\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Webkul\Event\Models\Event;
use Webkul\Event\Repositories\EventRepository;

class EventWriteService
{
    public function __construct(
        protected EventRepository $eventRepository
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int|string, mixed>  $fieldFiles
     * @param  array<int|string, mixed>  $retainedImages
     * @param  array<int|string, mixed>  $imageFiles
     */
    public function create(
        array $data,
        array $fieldFiles,
        array $retainedImages,
        array $imageFiles
    ): Event {
        $event = $this->eventRepository->create(
            $this->prepareData($data, $fieldFiles, false)
        );

        return $this->syncImages($event, $retainedImages, $imageFiles);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int|string, mixed>  $fieldFiles
     * @param  array<int|string, mixed>  $retainedImages
     * @param  array<int|string, mixed>  $imageFiles
     */
    public function update(
        int $eventId,
        array $data,
        array $fieldFiles,
        array $retainedImages,
        array $imageFiles
    ): Event {
        $event = $this->eventRepository->update(
            $this->prepareData($data, $fieldFiles, true),
            $eventId,
        );

        return $this->syncImages($event, $retainedImages, $imageFiles);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int|string, mixed>  $fieldFiles
     * @return array<string, mixed>
     */
    protected function prepareData(array $data, array $fieldFiles, bool $preserveFieldImages): array
    {
        $data['availability_use_end_date'] = true;
        unset($data['image'], $data['images']);

        foreach ((array) ($data['fields'] ?? []) as $key => $field) {
            if (($field['type'] ?? null) !== 'image') {
                continue;
            }

            $file = $fieldFiles[$key]['value'] ?? null;

            if (is_array($file)) {
                $file = $file[0] ?? null;
            }

            if ($file instanceof UploadedFile) {
                $data['fields'][$key]['value'] = $file->store('event_fields', 'public');
            } elseif ($preserveFieldImages) {
                $data['fields'][$key]['value'] = $field['old_value'] ?? null;
            }

            unset($data['fields'][$key]['old_value']);
        }

        return $data;
    }

    /**
     * @param  array<int|string, mixed>  $retainedImages
     * @param  array<int|string, mixed>  $imageFiles
     */
    protected function syncImages(Event $event, array $retainedImages, array $imageFiles): Event
    {
        $retainedPaths = array_values(array_filter(array_map(
            'strval',
            array_keys($retainedImages)
        )));

        $uploadedPaths = [];

        foreach ($imageFiles as $file) {
            if ($file instanceof UploadedFile) {
                $uploadedPaths[] = $file->store('events', 'public');
            }
        }

        $finalPaths = array_values(array_unique(array_filter([
            ...$retainedPaths,
            ...$uploadedPaths,
        ])));

        $currentPaths = $event->images()->pluck('path')->all();

        foreach (array_diff($currentPaths, $finalPaths) as $path) {
            Storage::disk('public')->delete($path);
        }

        $event->images()->delete();

        foreach ($finalPaths as $position => $path) {
            $event->images()->create([
                'path' => $path,
                'position' => $position,
            ]);
        }

        $event->image = $finalPaths[0] ?? null;
        $event->save();

        return $event;
    }
}
