<?php

namespace Webkul\Admin\Http\Controllers\Students;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Webkul\Admin\DataGrids\Students\StudentDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\MassDestroyRequest;
use Webkul\Event\Models\Event;
use Webkul\Student\Models\Student;
use Webkul\Student\Repositories\StudentRepository;

class StudentController extends Controller
{
    public function __construct(
        protected StudentRepository $studentRepository
    ) {}

    public function index(): View|JsonResponse|BinaryFileResponse
    {
        if (request()->ajax()) {
            return datagrid(StudentDataGrid::class)->process();
        }

        return view('admin::students.index');
    }

    public function search(): JsonResponse
    {
        $results = Student::query()
            ->select(['id', 'name', 'university_card_number'])
            ->where(function ($query) {
                $query->where('name', 'like', '%'.request()->input('query').'%')
                    ->orWhere('university_card_number', 'like', '%'.request()->input('query').'%');
            })
            ->limit(10)
            ->get()
            ->map(fn ($student) => [
                'id' => (int) $student->id,
                'name' => $student->name,
                'university_card_number' => $student->university_card_number,
            ])
            ->values();

        return response()->json([
            'data' => $results,
        ]);
    }

    public function create(): View
    {
        return view('admin::students.create');
    }

    public function store(): RedirectResponse
    {
        $data = $this->validate(request(), [
            'name' => ['required', 'string', 'max:255'],
            'university_card_number' => ['required', 'string', 'max:255', 'unique:students,university_card_number'],
            'registration_number' => ['nullable', 'string', 'max:255'],
            'major' => ['nullable', 'string', 'max:255'],
            'academic_level' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'profile_image' => ['nullable', 'image', 'max:2048'],
        ]);

        if (request()->hasFile('profile_image')) {
            $data['profile_image'] = request()->file('profile_image')->store('students', 'public');
        }

        $this->studentRepository->create($data);

        session()->flash('success', trans('admin::app.students.create-success'));

        return redirect()->route('admin.students.index');
    }

    public function show(int $id): View
    {
        $student = $this->studentRepository->with(['subscribedEvents' => function ($query) {
            $query->orderByDesc('event_date');
        }])->findOrFail($id);

        $events = Event::query()
            ->select(['id', 'title', 'event_date', 'status'])
            ->orderByDesc('event_date')
            ->get()
            ->filter(fn (Event $event) => $event->status && $event->isCurrentlyAvailable())
            ->values();

        return view('admin::students.view', compact('student', 'events'));
    }

    public function edit(int $id): View
    {
        $student = $this->studentRepository->findOrFail($id);

        return view('admin::students.edit', compact('student'));
    }

    public function update(int $id): RedirectResponse
    {
        $student = $this->studentRepository->findOrFail($id);

        $data = $this->validate(request(), [
            'name' => ['required', 'string', 'max:255'],
            'university_card_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('students', 'university_card_number')->ignore($student->id),
            ],
            'registration_number' => ['nullable', 'string', 'max:255'],
            'major' => ['nullable', 'string', 'max:255'],
            'academic_level' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
            'profile_image' => ['nullable', 'image', 'max:2048'],
        ]);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        if (request()->hasFile('profile_image')) {
            if ($student->profile_image) {
                Storage::disk('public')->delete($student->profile_image);
            }

            $data['profile_image'] = request()->file('profile_image')->store('students', 'public');
        }

        $this->studentRepository->update($data, $id);

        session()->flash('success', trans('admin::app.students.update-success'));

        return redirect()->route('admin.students.index');
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            DB::transaction(function () use ($id) {
                /** @var Student $student */
                $student = Student::query()->lockForUpdate()->findOrFail($id);

                $eventIds = $student->subscribedEvents()->pluck('events.id')->all();

                foreach ($eventIds as $eventId) {
                    $this->detachStudentFromEvent($student->id, (int) $eventId);
                }

                if ($student->profile_image) {
                    Storage::disk('public')->delete($student->profile_image);
                }

                $student->delete();
            });

            return response()->json([
                'message' => trans('admin::app.students.delete-success'),
            ]);
        } catch (Exception) {
            return response()->json([
                'message' => trans('admin::app.students.delete-failed'),
            ], 400);
        }
    }

    public function massDestroy(MassDestroyRequest $request): JsonResponse
    {
        $ids = array_map('intval', $request->input('indices', []));

        if ($ids === []) {
            return response()->json([
                'message' => trans('admin::app.students.no-selection'),
            ], 400);
        }

        try {
            DB::transaction(function () use ($ids) {
                $students = Student::query()
                    ->whereIn('id', $ids)
                    ->with('subscribedEvents:id')
                    ->lockForUpdate()
                    ->get();

                foreach ($students as $student) {
                    foreach ($student->subscribedEvents as $event) {
                        $this->detachStudentFromEvent((int) $student->id, (int) $event->id);
                    }

                    if ($student->profile_image) {
                        Storage::disk('public')->delete($student->profile_image);
                    }

                    $student->delete();
                }
            });

            return response()->json([
                'message' => trans('admin::app.students.all-delete-success'),
            ]);
        } catch (Exception) {
            return response()->json([
                'message' => trans('admin::app.students.delete-failed'),
            ], 400);
        }
    }

    public function storeSubscription(int $id): RedirectResponse
    {
        $data = $this->validate(request(), [
            'event_id' => ['required', 'integer', 'exists:events,id'],
        ]);

        /** @var Student $student */
        $student = Student::query()->findOrFail($id);

        $already = $student->subscribedEvents()->where('events.id', (int) $data['event_id'])->exists();

        if ($already) {
            session()->flash('info', trans('admin::app.students.subscriptions.already-exists'));

            return redirect()->route('admin.students.view', $id);
        }

        $event = Event::query()->findOrFail((int) $data['event_id']);

        if (! $event->status || ! $event->isCurrentlyAvailable()) {
            session()->flash('error', trans('admin::app.students.subscriptions.event-unavailable'));

            return redirect()->route('admin.students.view', $id);
        }

        $this->attachStudentToEvent((int) $student->id, (int) $data['event_id']);

        session()->flash('success', trans('admin::app.students.subscriptions.create-success'));

        return redirect()->route('admin.students.view', $id);
    }

    public function destroySubscription(int $id, int $eventId): RedirectResponse
    {
        $student = Student::query()->findOrFail($id);

        $exists = $student->subscribedEvents()->where('events.id', $eventId)->exists();

        if (! $exists) {
            session()->flash('info', trans('admin::app.students.subscriptions.not-found'));

            return redirect()->route('admin.students.view', $id);
        }

        $this->detachStudentFromEvent((int) $student->id, $eventId);

        session()->flash('success', trans('admin::app.students.subscriptions.delete-success'));

        return redirect()->route('admin.students.view', $id);
    }

    protected function attachStudentToEvent(int $studentId, int $eventId): void
    {
        DB::transaction(function () use ($studentId, $eventId) {
            $event = Event::query()->whereKey($eventId)->lockForUpdate()->firstOrFail();

            $already = DB::table('event_student')
                ->where('event_id', $eventId)
                ->where('student_id', $studentId)
                ->exists();

            if ($already) {
                return;
            }

            DB::table('event_student')->insert([
                'event_id' => $eventId,
                'student_id' => $studentId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($event->availability_use_seats && $event->available_seats !== null) {
                $event->available_seats = max(0, (int) $event->available_seats - 1);
                $event->save();
            }
        });
    }

    protected function detachStudentFromEvent(int $studentId, int $eventId): void
    {
        DB::transaction(function () use ($studentId, $eventId) {
            $event = Event::query()->whereKey($eventId)->lockForUpdate()->first();

            if (! $event) {
                DB::table('event_student')
                    ->where('event_id', $eventId)
                    ->where('student_id', $studentId)
                    ->delete();

                return;
            }

            $deleted = DB::table('event_student')
                ->where('event_id', $eventId)
                ->where('student_id', $studentId)
                ->delete();

            if ($deleted && $event->availability_use_seats && $event->available_seats !== null) {
                $event->available_seats = (int) $event->available_seats + 1;
                $event->save();
            }
        });
    }
}
