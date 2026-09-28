<?php

namespace Webkul\Event\Repositories;

use Illuminate\Support\Collection;
use Webkul\Core\Eloquent\Repository;
use Webkul\Event\Contracts\Event;

class EventRepository extends Repository
{
    /**
     * Specify model class name.
     *
     * @return mixed
     */
    public function model()
    {
        return Event::class;
    }

    /**
     * @param  array<string, mixed>  $field
     * @return array{name: string, type: string, value: string|null}|null
     */
    protected function normalizeEventFieldRow(mixed $field): ?array
    {
        if (! is_array($field)) {
            return null;
        }

        $name = trim((string) ($field['name'] ?? ''));
        $type = trim((string) ($field['type'] ?? ''));
        $value = $field['value'] ?? null;

        if ($value === '' || $value === false) {
            $value = null;
        }

        if ($value !== null && ! is_scalar($value)) {
            $value = null;
        }

        return [
            'name' => $name,
            'type' => $type,
            'value' => $value === null ? null : (string) $value,
        ];
    }

    /**
     * @param  list<int>  $ids
     * @return list<int>
     */
    protected function filterRelatedIdsForEvent(int $eventId, array $ids): array
    {
        return array_values(array_unique(array_filter(
            array_map('intval', $ids),
            static fn (int $id) => $id > 0 && $id !== $eventId
        )));
    }

    public function create(array $data)
    {
        $categoryIds = [];
        if (array_key_exists('category_ids', $data)) {
            $categoryIds = array_values(array_unique(array_map(
                'intval',
                array_filter((array) $data['category_ids'], static fn ($id) => $id !== '' && $id !== null)
            )));
        }
        unset($data['category_ids']);

        $fieldsPayload = false;
        if (! empty($data['event_custom_fields_form'])) {
            $fieldsPayload = isset($data['fields']) && is_array($data['fields'])
                ? $data['fields']
                : [];
            unset($data['fields']);
        }
        unset($data['event_custom_fields_form']);

        $event = parent::create($data);

        if ($categoryIds !== []) {
            $event->categories()->sync($categoryIds);
        }

        if ($fieldsPayload !== false) {
            foreach ($fieldsPayload as $field) {
                $row = $this->normalizeEventFieldRow($field);
                if ($row && $row['name'] !== '' && $row['type'] !== '') {
                    $event->fields()->create($row);
                }
            }
        }

        return $event;
    }

    public function update(array $data, $id, $attribute = 'id')
    {
        $categoryIds = null;
        if (array_key_exists('category_ids', $data)) {
            $categoryIds = array_values(array_unique(array_map(
                'intval',
                array_filter((array) $data['category_ids'], static fn ($cid) => $cid !== '' && $cid !== null)
            )));
            unset($data['category_ids']);
        }

        $fieldsPayload = false;
        if (! empty($data['event_custom_fields_form'])) {
            $fieldsPayload = isset($data['fields']) && is_array($data['fields'])
                ? $data['fields']
                : [];
            unset($data['fields']);
        }
        unset($data['event_custom_fields_form']);

        $event = parent::update($data, $id, $attribute);

        if ($categoryIds !== null) {
            $event->categories()->sync($categoryIds);
        }

        if ($fieldsPayload !== false) {
            $event->fields()->delete();
            foreach ($fieldsPayload as $field) {
                $row = $this->normalizeEventFieldRow($field);
                if ($row && $row['name'] !== '' && $row['type'] !== '') {
                    $event->fields()->create($row);
                }
            }
        }

        return $event;
    }

    public function searchByTitle(string $query, ?int $excludedId = null): Collection
    {
        $builder = $this->getModel()
            ->newQuery()
            ->where('title', 'like', '%'.$query.'%');

        if ($excludedId !== null) {
            $builder->whereKeyNot($excludedId);
        }

        return $builder->get();
    }

    public function availableForSubscription(): Collection
    {
        return $this->getModel()
            ->newQuery()
            ->select(['id', 'title', 'event_date', 'event_end_date', 'available_seats', 'availability_use_seats', 'availability_use_end_date', 'status'])
            ->orderByDesc('event_date')
            ->get()
            ->filter(fn ($event): bool => $event->status && $event->isCurrentlyAvailable())
            ->values();
    }

    public function subscribedByStudent(int $studentId): Collection
    {
        return $this->getModel()
            ->newQuery()
            ->join('event_student', 'events.id', '=', 'event_student.event_id')
            ->where('event_student.student_id', $studentId)
            ->select('events.*', 'event_student.created_at as subscribed_at')
            ->orderByDesc('events.event_date')
            ->get();
    }
}
