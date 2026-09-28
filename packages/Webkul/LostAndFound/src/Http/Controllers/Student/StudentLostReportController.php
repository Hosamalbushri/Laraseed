<?php

namespace Webkul\LostAndFound\Http\Controllers\Student;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Webkul\LostAndFound\Http\Requests\Student\StoreLostReportRequest;
use Webkul\LostAndFound\Http\Requests\Student\UpdateLostReportRequest;
use Webkul\LostAndFound\Http\Requests\Student\UploadLostReportImageRequest;
use Webkul\LostAndFound\Models\LostReport;
use Webkul\LostAndFound\Services\Application\StudentReportApplicationService;

class StudentLostReportController extends Controller
{
    public function __construct(
        protected StudentReportApplicationService $reportAppService
    ) {}

    public function store(StoreLostReportRequest $request): JsonResponse|RedirectResponse
    {
        $student = auth('student')->user();
        $report = $this->reportAppService->createLostReport($student, $request->validatedData());

        if ($request->wantsJson()) {
            return response()->json([
                'message' => trans('lost_found::app.student.reports.created_success'),
                'data' => [
                    'id' => $report->id,
                    'public_reference' => $report->public_reference,
                    'title' => $report->title,
                    'status' => $report->status->value,
                ],
            ], 201);
        }

        return redirect()->back()->with('success', trans('lost_found::app.student.reports.created_success'));
    }

    public function update(UpdateLostReportRequest $request, int $id): JsonResponse|RedirectResponse
    {
        $student = auth('student')->user();
        $report = LostReport::where('student_id', $student->id)->findOrFail($id);

        $updated = $this->reportAppService->updateOwnLostReport($student, $report, $request->validatedData());

        if ($request->wantsJson()) {
            return response()->json([
                'message' => trans('lost_found::app.student.reports.updated_success'),
                'data' => [
                    'id' => $updated->id,
                    'title' => $updated->title,
                ],
            ]);
        }

        return redirect()->back()->with('success', trans('lost_found::app.student.reports.updated_success'));
    }

    public function uploadImage(UploadLostReportImageRequest $request, int $id): JsonResponse|RedirectResponse
    {
        $student = auth('student')->user();
        $report = LostReport::where('student_id', $student->id)->findOrFail($id);

        $image = $this->reportAppService->addOwnLostReportImage($student, $report, $request->file('image'));

        if ($request->wantsJson()) {
            return response()->json([
                'message' => trans('lost_found::app.student.reports.image_uploaded_success'),
                'data' => [
                    'id' => $image->id,
                    'sort_order' => $image->sort_order,
                ],
            ], 201);
        }

        return redirect()->back()->with('success', trans('lost_found::app.student.reports.image_uploaded_success'));
    }
}
