<?php

namespace Webkul\LostAndFound\Http\Controllers\Student;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Webkul\LostAndFound\Enums\EvidenceType;
use Webkul\LostAndFound\Http\Requests\Student\StoreClaimEvidenceRequest;
use Webkul\LostAndFound\Http\Requests\Student\StoreClaimRequest;
use Webkul\LostAndFound\Http\Requests\Student\UploadClaimEvidenceImageRequest;
use Webkul\LostAndFound\Models\FoundItem;
use Webkul\LostAndFound\Models\LostFoundClaim;
use Webkul\LostAndFound\Services\Application\StudentClaimApplicationService;

class StudentClaimController extends Controller
{
    public function __construct(
        protected StudentClaimApplicationService $claimAppService
    ) {}

    public function store(StoreClaimRequest $request): JsonResponse|RedirectResponse
    {
        $student = auth('student')->user();
        $item = FoundItem::findOrFail($request->validated('found_item_id'));

        $claim = $this->claimAppService->submitClaim($student, $item, $request->validatedData());

        if ($request->wantsJson()) {
            return response()->json([
                'message' => trans('lost_found::app.student.claims.submitted_success'),
                'data' => [
                    'id' => $claim->id,
                    'status' => $claim->status->value,
                    'submitted_at' => $claim->created_at?->toIso8601String(),
                ],
            ], 201);
        }

        return redirect()->back()->with('success', trans('lost_found::app.student.claims.submitted_success'));
    }

    public function addEvidence(StoreClaimEvidenceRequest $request, int $id): JsonResponse|RedirectResponse
    {
        $student = auth('student')->user();
        $claim = LostFoundClaim::where('claimant_student_id', $student->id)->findOrFail($id);

        $type = EvidenceType::from($request->validated('type'));
        $evidence = $this->claimAppService->addOwnClaimEvidence(
            $student,
            $claim,
            $type,
            $request->validated('content')
        );

        if ($request->wantsJson()) {
            return response()->json([
                'message' => trans('lost_found::app.student.claims.evidence_added_success'),
                'data' => [
                    'id' => $evidence->id,
                    'type' => $evidence->evidence_type->value,
                ],
            ], 201);
        }

        return redirect()->back()->with('success', trans('lost_found::app.student.claims.evidence_added_success'));
    }

    public function uploadImage(UploadClaimEvidenceImageRequest $request, int $id): JsonResponse|RedirectResponse
    {
        $student = auth('student')->user();
        $claim = LostFoundClaim::where('claimant_student_id', $student->id)->findOrFail($id);

        $evidence = $this->claimAppService->addOwnClaimEvidence(
            $student,
            $claim,
            EvidenceType::IMAGE_ATTACHMENT,
            $request->file('image')
        );

        if ($request->wantsJson()) {
            return response()->json([
                'message' => trans('lost_found::app.student.claims.image_added_success'),
                'data' => [
                    'id' => $evidence->id,
                    'type' => EvidenceType::IMAGE_ATTACHMENT->value,
                ],
            ], 201);
        }

        return redirect()->back()->with('success', trans('lost_found::app.student.claims.image_added_success'));
    }

    public function withdraw(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $student = auth('student')->user();
        $claim = LostFoundClaim::where('claimant_student_id', $student->id)->findOrFail($id);

        $withdrawn = $this->claimAppService->withdrawOwnClaim($student, $claim);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => trans('lost_found::app.student.claims.withdrawn_success'),
                'data' => [
                    'id' => $withdrawn->id,
                    'status' => $withdrawn->status->value,
                ],
            ]);
        }

        return redirect()->back()->with('success', trans('lost_found::app.student.claims.withdrawn_success'));
    }
}
