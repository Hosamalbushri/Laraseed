<?php

namespace Webkul\LostAndFound\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Webkul\LostAndFound\Enums\ClaimStatus;
use Webkul\LostAndFound\Http\Requests\Employee\ApproveClaimRequest;
use Webkul\LostAndFound\Http\Requests\Employee\RejectClaimRequest;
use Webkul\LostAndFound\Http\Requests\Employee\ReviewClaimRequest;
use Webkul\LostAndFound\Http\Requests\Employee\RevokeClaimApprovalRequest;
use Webkul\LostAndFound\Models\LostFoundClaim;
use Webkul\LostAndFound\Services\Application\EmployeeClaimApplicationService;

class EmployeeClaimController extends Controller
{
    public function __construct(
        protected EmployeeClaimApplicationService $applicationService
    ) {}

    public function review(ReviewClaimRequest $request, int $id): JsonResponse
    {
        $actor = auth('user')->user();
        $claim = LostFoundClaim::findOrFail($id);

        $statusInput = $request->validated('status');
        $newStatus = $statusInput ? ClaimStatus::from($statusInput) : ClaimStatus::UNDER_REVIEW;
        $notes = $request->validated('notes');

        $updatedClaim = $this->applicationService->reviewClaim($actor, $claim, $newStatus, $notes);

        return response()->json([
            'message' => trans('lost_found::app.admin.claims.reviewed_success'),
            'data' => [
                'id' => $updatedClaim->id,
                'found_item_id' => $updatedClaim->found_item_id,
                'status' => $updatedClaim->status->value,
                'submitted_at' => $updatedClaim->submitted_at?->toISOString(),
            ],
        ], 200);
    }

    public function approve(ApproveClaimRequest $request, int $id): JsonResponse
    {
        $actor = auth('user')->user();
        $claim = LostFoundClaim::findOrFail($id);
        $notes = $request->validated('notes');

        $updatedClaim = $this->applicationService->approveClaim($actor, $claim, $notes);

        return response()->json([
            'message' => trans('lost_found::app.admin.claims.approved_success'),
            'data' => [
                'id' => $updatedClaim->id,
                'found_item_id' => $updatedClaim->found_item_id,
                'status' => $updatedClaim->status->value,
                'submitted_at' => $updatedClaim->submitted_at?->toISOString(),
            ],
        ], 200);
    }

    public function reject(RejectClaimRequest $request, int $id): JsonResponse
    {
        $actor = auth('user')->user();
        $claim = LostFoundClaim::findOrFail($id);
        $notes = $request->validated('notes');

        $updatedClaim = $this->applicationService->rejectClaim($actor, $claim, $notes);

        return response()->json([
            'message' => trans('lost_found::app.admin.claims.rejected_success'),
            'data' => [
                'id' => $updatedClaim->id,
                'found_item_id' => $updatedClaim->found_item_id,
                'status' => $updatedClaim->status->value,
                'submitted_at' => $updatedClaim->submitted_at?->toISOString(),
            ],
        ], 200);
    }

    public function revoke(RevokeClaimApprovalRequest $request, int $id): JsonResponse
    {
        $actor = auth('user')->user();
        $claim = LostFoundClaim::findOrFail($id);
        $reason = $request->validated('reason') ?? $request->validated('notes');

        $updatedClaim = $this->applicationService->revokeClaimApproval($actor, $claim, $reason);

        return response()->json([
            'message' => trans('lost_found::app.admin.claims.revoked_success'),
            'data' => [
                'id' => $updatedClaim->id,
                'found_item_id' => $updatedClaim->found_item_id,
                'status' => $updatedClaim->status->value,
                'submitted_at' => $updatedClaim->submitted_at?->toISOString(),
            ],
        ], 200);
    }
}
