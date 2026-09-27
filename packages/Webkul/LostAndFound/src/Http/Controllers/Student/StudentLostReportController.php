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
                'message' => 'Lost report created successfully.',
                'data' => [
                    'id' => $report->id,
                    'public_reference' => $report->public_reference,
                    'title' => $report->title,
                    'status' => $report->status->value,
                ],
            ], 201);
        }

        return redirect()->back()->with('success', 'Lost report created successfully.');
    }

    public function update(UpdateLostReportRequest $request, int $id): JsonResponse|RedirectResponse
    {
        $student = auth('student')->user();
        $report = LostReport::where('student_id', $student->id)->findOrFail($id);

        $updated = $this->reportAppService->updateOwnLostReport($student, $report, $request->validatedData());

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Lost report updated successfully.',
                'data' => [
                    'id' => $updated->id,
                    'title' => $updated->title,
                ],
            ]);
        }

        return redirect()->back()->with('success', 'Lost report updated successfully.');
    }

    public function uploadImage(UploadLostReportImageRequest $request, int $id): JsonResponse|RedirectResponse
    {
        $student = auth('student')->user();
        $report = LostReport::where('student_id', $student->id)->findOrFail($id);

        $image = $this->reportAppService->addOwnLostReportImage($student, $report, $request->file('image'));

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Image uploaded successfully.',
                'data' => [
                    'id' => $image->id,
                    'sort_order' => $image->sort_order,
                ],
            ], 201);
        }

        return redirect()->back()->with('success', 'Image uploaded successfully.');
    }
}
