<?php

namespace Webkul\LostAndFound\Http\Controllers\Employee;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Webkul\LostAndFound\DataGrids\Employee\ClaimDataGrid;
use Webkul\LostAndFound\Enums\EvidenceType;
use Webkul\LostAndFound\Models\ClaimEvidence;
use Webkul\LostAndFound\Models\ClaimReview;
use Webkul\LostAndFound\Services\Application\LostAndFoundAuthorization;

class EmployeeClaimReadController extends Controller
{
    public function index(int $id): View|JsonResponse
    {
        $this->authorizeRead();

        $item = DB::table('lost_found_items')->where('id', $id)
            ->first(['id', 'public_reference', 'title', 'status']);
        abort_unless($item, 404);

        if (request()->ajax()) {
            return datagrid(ClaimDataGrid::class)->process();
        }

        return view('lost_found::employee.claims.index', compact('item'));
    }

    public function show(int $id): View|JsonResponse
    {
        $this->authorizeRead();

        $claim = DB::table('lost_found_claims')
            ->join('lost_found_items', 'lost_found_claims.found_item_id', '=', 'lost_found_items.id')
            ->join('students', 'lost_found_claims.claimant_student_id', '=', 'students.id')
            ->where('lost_found_claims.id', $id)
            ->first([
                'lost_found_claims.id', 'lost_found_claims.found_item_id', 'lost_found_claims.status',
                'lost_found_claims.submitted_at', 'lost_found_claims.created_at', 'lost_found_claims.updated_at',
                'lost_found_items.public_reference', 'lost_found_items.title as item_title',
                'lost_found_items.status as item_status', 'lost_found_items.approved_claim_id',
                'students.id as claimant_student_id', 'students.name as claimant_name',
            ]);
        abort_unless($claim, 404);

        $detail = [
            'id' => $claim->id,
            'found_item' => [
                'id' => $claim->found_item_id,
                'public_reference' => $claim->public_reference,
                'title' => $claim->item_title,
                'status' => $claim->item_status,
            ],
            'status' => $claim->status,
            'claimant' => ['id' => $claim->claimant_student_id, 'name' => $claim->claimant_name],
            'submitted_at' => $claim->submitted_at,
            'created_at' => $claim->created_at,
            'updated_at' => $claim->updated_at,
            'is_approved_claim' => (int) $claim->approved_claim_id === (int) $claim->id,
            'evidence' => ClaimEvidence::query()->where('claim_id', $id)
                ->orderBy('id')->get(['id', 'claim_id', 'evidence_type', 'text_value', 'submitted_at'])
                ->map(static fn (ClaimEvidence $evidence): array => [
                    'id' => $evidence->id,
                    'type' => $evidence->evidence_type->value,
                    'text' => $evidence->evidence_type === EvidenceType::IMAGE_ATTACHMENT ? null : $evidence->text_value,
                    'submitted_at' => $evidence->submitted_at?->toISOString(),
                ])->all(),
            'reviews' => ClaimReview::query()->with('reviewer:id,name')->where('claim_id', $id)
                ->orderBy('id')->get(['id', 'claim_id', 'reviewer_user_id', 'from_status', 'to_status', 'staff_notes', 'reviewed_at'])
                ->map(static fn (ClaimReview $review): array => [
                    'id' => $review->id,
                    'from_status' => $review->from_status->value,
                    'to_status' => $review->to_status->value,
                    'staff_notes' => $review->staff_notes,
                    'reviewed_at' => $review->reviewed_at?->toISOString(),
                    'reviewer_name' => $review->reviewer?->name,
                ])->all(),
        ];

        if (request()->expectsJson()) {
            return response()->json(['data' => $detail]);
        }

        return view('lost_found::employee.claims.show', compact('detail'));
    }

    private function authorizeRead(): void
    {
        LostAndFoundAuthorization::authorizeUser(auth('user')->user(), 'lost_found.claims.view');
    }
}
