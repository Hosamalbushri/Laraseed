<?php

namespace Webkul\Student\Http\Controllers\Admin;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Student\DataGrids\StudentDataGrid;
use Webkul\Student\Http\Requests\Admin\CreateStudentRequest;
use Webkul\Student\Http\Requests\Admin\MassDestroyRequest;
use Webkul\Student\Http\Requests\Admin\UpdateStudentRequest;
use Webkul\Student\Models\Student;
use Webkul\Student\Repositories\StudentRepository;
use Webkul\Student\Services\StudentAdminService;

class StudentController extends Controller
{
    public function __construct(
        protected StudentRepository $studentRepository,
        protected StudentAdminService $studentAdminService
    ) {}

    public function index(): View|JsonResponse|BinaryFileResponse
    {
        if (request()->ajax()) {
            return datagrid(StudentDataGrid::class)->process();
        }

        return view('student::admin.students.index');
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
        return view('student::admin.students.create');
    }

    public function store(CreateStudentRequest $request): RedirectResponse
    {
        $this->studentAdminService->createStudent(
            $request->validated(),
            $request->file('profile_image')
        );

        session()->flash('success', trans('student::app.students.create-success'));

        return redirect()->route('admin.students.index');
    }

    public function show(int $id): View
    {
        $student = $this->studentRepository->findOrFail($id);

        return view('student::admin.students.view', compact('student'));
    }

    public function edit(int $id): View
    {
        $student = $this->studentRepository->findOrFail($id);

        return view('student::admin.students.edit', compact('student'));
    }

    public function update(UpdateStudentRequest $request, int $id): RedirectResponse
    {
        $this->studentAdminService->updateStudent(
            $id,
            $request->validated(),
            $request->file('profile_image')
        );

        session()->flash('success', trans('student::app.students.update-success'));

        return redirect()->route('admin.students.index');
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->studentAdminService->deleteStudent($id);

            return response()->json([
                'message' => trans('student::app.students.delete-success'),
            ]);
        } catch (Exception) {
            return response()->json([
                'message' => trans('student::app.students.delete-failed'),
            ], 400);
        }
    }

    public function massDestroy(MassDestroyRequest $request): JsonResponse
    {
        $ids = array_map('intval', $request->input('indices', []));

        if ($ids === []) {
            return response()->json([
                'message' => trans('student::app.students.no-selection'),
            ], 400);
        }

        try {
            $this->studentAdminService->massDeleteStudents($ids);

            return response()->json([
                'message' => trans('student::app.students.all-delete-success'),
            ]);
        } catch (Exception) {
            return response()->json([
                'message' => trans('student::app.students.delete-failed'),
            ], 400);
        }
    }
}
