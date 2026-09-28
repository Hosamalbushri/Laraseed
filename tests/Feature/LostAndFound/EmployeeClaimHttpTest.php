<?php

namespace Tests\Feature\LostAndFound;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;
use Webkul\LostAndFound\Enums\ClaimStatus;
use Webkul\LostAndFound\Enums\ItemStatus;
use Webkul\LostAndFound\Models\ClaimReview;
use Webkul\LostAndFound\Models\FoundItem;
use Webkul\LostAndFound\Models\Handover;
use Webkul\LostAndFound\Models\LostFoundCategory;
use Webkul\LostAndFound\Models\LostFoundClaim;
use Webkul\LostAndFound\Services\Application\EmployeeClaimApplicationService;
use Webkul\Student\Models\Student;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

class EmployeeClaimHttpTest extends TestCase
{
    use DatabaseTransactions;

    protected function createCategory(): LostFoundCategory
    {
        return LostFoundCategory::create([
            'code' => 'cat-'.Str::random(6),
            'sort_order' => 1,
            'is_active' => true,
        ]);
    }

    protected function createEmployeeWithPermissions(array $permissions): User
    {
        $role = Role::create([
            'name' => 'Staff Role '.Str::random(4),
            'permission_type' => 'custom',
            'permissions' => $permissions,
        ]);

        return User::create([
            'name' => 'Staff '.Str::random(6),
            'email' => 'staff-'.Str::random(6).'@example.com',
            'password' => Hash::make('password'),
            'role_id' => $role->id,
            'status' => 1,
        ]);
    }

    protected function createSuperAdminUser(): User
    {
        $role = Role::create([
            'name' => 'Admin Role '.Str::random(4),
            'permission_type' => 'all',
        ]);

        return User::create([
            'name' => 'Admin '.Str::random(6),
            'email' => 'admin-'.Str::random(6).'@example.com',
            'password' => Hash::make('password'),
            'role_id' => $role->id,
            'status' => 1,
        ]);
    }

    protected function createStudent(): Student
    {
        return Student::create([
            'university_card_number' => 'STU-'.Str::random(8),
            'name' => 'Student '.Str::random(6),
            'password' => Hash::make('password'),
        ]);
    }

    protected function createFoundItem(?User $user = null): FoundItem
    {
        $user = $user ?? $this->createSuperAdminUser();
        $category = $this->createCategory();

        return FoundItem::create([
            'category_id' => $category->id,
            'logged_by_user_id' => $user->id,
            'public_reference' => 'FI-'.strtoupper(Str::random(10)),
            'title' => 'Found Item '.Str::random(4),
            'found_at' => now(),
            'found_location' => 'Library',
            'status' => ItemStatus::REPORTED,
        ]);
    }

    protected function createClaim(FoundItem $item, ?Student $student = null, ClaimStatus $status = ClaimStatus::UNDER_REVIEW): LostFoundClaim
    {
        $student = $student ?? $this->createStudent();

        return LostFoundClaim::create([
            'found_item_id' => $item->id,
            'claimant_student_id' => $student->id,
            'status' => $status,
            'submitted_at' => now(),
        ]);
    }

    public function test_authenticated_employee_with_permission_can_review_claim(): void
    {
        $user = $this->createEmployeeWithPermissions(['lost_found.claims.review']);
        $item = $this->createFoundItem();
        $claim = $this->createClaim($item, status: ClaimStatus::UNDER_REVIEW);

        $response = $this->actingAs($user, 'user')
            ->postJson(route('admin.lost_found.claims.review', $claim->id), [
                'status' => 'needs_information',
                'notes' => 'Please provide proof of purchase.',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', ClaimStatus::NEEDS_INFORMATION->value);

        $this->assertDatabaseHas('lost_found_claims', [
            'id' => $claim->id,
            'status' => ClaimStatus::NEEDS_INFORMATION->value,
        ]);

        $review = ClaimReview::where('claim_id', $claim->id)->latest('id')->first();
        $this->assertNotNull($review);
        $this->assertEquals($user->id, $review->reviewer_user_id);
        $this->assertEquals(ClaimStatus::NEEDS_INFORMATION, $review->to_status);
        $this->assertEquals('Please provide proof of purchase.', $review->staff_notes);
    }

    public function test_authenticated_employee_with_permission_can_approve_claim(): void
    {
        $user = $this->createEmployeeWithPermissions(['lost_found.claims.approve']);
        $item = $this->createFoundItem();
        $claim = $this->createClaim($item, status: ClaimStatus::UNDER_REVIEW);

        $response = $this->actingAs($user, 'user')
            ->postJson(route('admin.lost_found.claims.approve', $claim->id), [
                'notes' => 'Approved after evidence verification.',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', ClaimStatus::APPROVED->value);

        $item->refresh();
        $this->assertEquals($claim->id, $item->approved_claim_id);

        $review = ClaimReview::where('claim_id', $claim->id)->latest('id')->first();
        $this->assertNotNull($review);
        $this->assertEquals($user->id, $review->reviewer_user_id);
        $this->assertEquals(ClaimStatus::APPROVED, $review->to_status);
        $this->assertEquals('Approved after evidence verification.', $review->staff_notes);
    }

    public function test_authenticated_employee_with_permission_can_reject_claim(): void
    {
        $user = $this->createEmployeeWithPermissions(['lost_found.claims.reject']);
        $item = $this->createFoundItem();
        $claim = $this->createClaim($item, status: ClaimStatus::UNDER_REVIEW);

        $response = $this->actingAs($user, 'user')
            ->postJson(route('admin.lost_found.claims.reject', $claim->id), [
                'notes' => 'Evidence does not match.',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', ClaimStatus::REJECTED->value);

        $this->assertDatabaseHas('lost_found_claims', [
            'id' => $claim->id,
            'status' => ClaimStatus::REJECTED->value,
        ]);

        $review = ClaimReview::where('claim_id', $claim->id)->latest('id')->first();
        $this->assertNotNull($review);
        $this->assertEquals($user->id, $review->reviewer_user_id);
        $this->assertEquals(ClaimStatus::REJECTED, $review->to_status);
        $this->assertEquals('Evidence does not match.', $review->staff_notes);
    }

    public function test_authenticated_employee_with_permission_can_revoke_claim_approval(): void
    {
        $user = $this->createEmployeeWithPermissions(['lost_found.claims.approve']);
        $item = $this->createFoundItem();
        $claim = $this->createClaim($item, status: ClaimStatus::UNDER_REVIEW);

        // Approve first
        $this->actingAs($user, 'user')
            ->postJson(route('admin.lost_found.claims.approve', $claim->id));

        $item->refresh();
        $this->assertEquals($claim->id, $item->approved_claim_id);

        // Revoke
        $response = $this->actingAs($user, 'user')
            ->postJson(route('admin.lost_found.claims.revoke', $claim->id), [
                'reason' => 'Approved in error.',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', ClaimStatus::REJECTED->value);

        $item->refresh();
        $this->assertNull($item->approved_claim_id);

        $this->assertDatabaseHas('lost_found_claims', [
            'id' => $claim->id,
            'status' => ClaimStatus::REJECTED->value,
        ]);
    }

    public function test_unauthenticated_request_returns_redirect(): void
    {
        $item = $this->createFoundItem();
        $claim = $this->createClaim($item);

        $htmlResponse = $this->post(route('admin.lost_found.claims.approve', $claim->id));
        $this->assertEquals(302, $htmlResponse->status());

        $jsonResponse = $this->postJson(route('admin.lost_found.claims.approve', $claim->id));
        $this->assertEquals(302, $jsonResponse->status());
    }

    public function test_student_guard_user_cannot_access_employee_claim_endpoints(): void
    {
        $student = $this->createStudent();
        $item = $this->createFoundItem();
        $claim = $this->createClaim($item);

        $response = $this->actingAs($student, 'student')
            ->postJson(route('admin.lost_found.claims.approve', $claim->id));

        $this->assertEquals(302, $response->status());
    }

    public function test_employee_without_permission_is_denied(): void
    {
        $user = $this->createEmployeeWithPermissions(['unrelated.permission']);
        $item = $this->createFoundItem();
        $claim = $this->createClaim($item);

        $response = $this->actingAs($user, 'user')
            ->postJson(route('admin.lost_found.claims.approve', $claim->id));

        $this->assertEquals(401, $response->status());
    }

    public function test_permission_isolation_view_only_cannot_mutate_claim(): void
    {
        $user = $this->createEmployeeWithPermissions(['lost_found.claims.view']);
        $item = $this->createFoundItem();
        $claim = $this->createClaim($item);

        $reviewResp = $this->actingAs($user, 'user')
            ->postJson(route('admin.lost_found.claims.review', $claim->id));
        $this->assertEquals(401, $reviewResp->status());

        $approveResp = $this->actingAs($user, 'user')
            ->postJson(route('admin.lost_found.claims.approve', $claim->id));
        $this->assertEquals(401, $approveResp->status());

        $rejectResp = $this->actingAs($user, 'user')
            ->postJson(route('admin.lost_found.claims.reject', $claim->id));
        $this->assertEquals(401, $rejectResp->status());

        $revokeResp = $this->actingAs($user, 'user')
            ->postJson(route('admin.lost_found.claims.revoke', $claim->id));
        $this->assertEquals(401, $revokeResp->status());
    }

    public function test_review_permission_cannot_approve_claim(): void
    {
        $user = $this->createEmployeeWithPermissions(['lost_found.claims.review']);
        $item = $this->createFoundItem();
        $claim = $this->createClaim($item);

        $response = $this->actingAs($user, 'user')
            ->postJson(route('admin.lost_found.claims.approve', $claim->id));

        $this->assertEquals(401, $response->status());
    }

    public function test_approve_permission_cannot_reject_claim(): void
    {
        $user = $this->createEmployeeWithPermissions(['lost_found.claims.approve']);
        $item = $this->createFoundItem();
        $claim = $this->createClaim($item);

        $response = $this->actingAs($user, 'user')
            ->postJson(route('admin.lost_found.claims.reject', $claim->id));

        $this->assertEquals(401, $response->status());
    }

    public function test_unrelated_permission_cannot_access_claim_operations(): void
    {
        $user = $this->createEmployeeWithPermissions(['lost_found.items.edit']);
        $item = $this->createFoundItem();
        $claim = $this->createClaim($item);

        $response = $this->actingAs($user, 'user')
            ->postJson(route('admin.lost_found.claims.approve', $claim->id));

        $this->assertEquals(401, $response->status());
    }

    public function test_status_spoofing_attempts_on_review_endpoint_are_rejected(): void
    {
        $user = $this->createEmployeeWithPermissions(['lost_found.claims.review']);
        $item = $this->createFoundItem();
        $claim = $this->createClaim($item, status: ClaimStatus::UNDER_REVIEW);

        $approvedAttempt = $this->actingAs($user, 'user')
            ->postJson(route('admin.lost_found.claims.review', $claim->id), ['status' => 'approved']);
        $approvedAttempt->assertStatus(422);

        $rejectedAttempt = $this->actingAs($user, 'user')
            ->postJson(route('admin.lost_found.claims.review', $claim->id), ['status' => 'rejected']);
        $rejectedAttempt->assertStatus(422);

        $withdrawnAttempt = $this->actingAs($user, 'user')
            ->postJson(route('admin.lost_found.claims.review', $claim->id), ['status' => 'withdrawn']);
        $withdrawnAttempt->assertStatus(422);

        $submittedAttempt = $this->actingAs($user, 'user')
            ->postJson(route('admin.lost_found.claims.review', $claim->id), ['status' => 'submitted']);
        $submittedAttempt->assertStatus(422);

        $claim->refresh();
        $this->assertEquals(ClaimStatus::UNDER_REVIEW, $claim->status);
    }

    public function test_duplicate_approval_attempt_on_already_approved_claim_fails(): void
    {
        $user = $this->createEmployeeWithPermissions(['lost_found.claims.approve']);
        $item = $this->createFoundItem();
        $claim = $this->createClaim($item, status: ClaimStatus::UNDER_REVIEW);

        // Initial approval succeeds
        $firstResponse = $this->actingAs($user, 'user')
            ->postJson(route('admin.lost_found.claims.approve', $claim->id));
        $firstResponse->assertStatus(200);

        $initialReviewsCount = ClaimReview::where('claim_id', $claim->id)->count();

        // Second approval attempt on same claim
        $secondResponse = $this->actingAs($user, 'user')
            ->postJson(route('admin.lost_found.claims.approve', $claim->id));

        $this->assertNotEquals(200, $secondResponse->status());

        // Review count must not change
        $this->assertEquals($initialReviewsCount, ClaimReview::where('claim_id', $claim->id)->count());
    }

    public function test_reviewer_user_id_spoofing_in_payload_is_ignored(): void
    {
        $user1 = $this->createEmployeeWithPermissions(['lost_found.claims.approve']);
        $user2 = $this->createSuperAdminUser();
        $item = $this->createFoundItem();
        $claim = $this->createClaim($item, status: ClaimStatus::UNDER_REVIEW);

        $response = $this->actingAs($user1, 'user')
            ->postJson(route('admin.lost_found.claims.approve', $claim->id), [
                'reviewer_user_id' => $user2->id,
                'user_id' => $user2->id,
                'approved_by' => $user2->id,
            ]);

        $response->assertStatus(200);

        $review = ClaimReview::where('claim_id', $claim->id)->latest('id')->first();
        $this->assertNotNull($review);
        $this->assertEquals($user1->id, $review->reviewer_user_id);
    }

    public function test_claimant_student_id_and_item_id_spoofing_are_ignored(): void
    {
        $user = $this->createEmployeeWithPermissions(['lost_found.claims.approve']);
        $student2 = $this->createStudent();
        $item1 = $this->createFoundItem();
        $item2 = $this->createFoundItem();
        $claim = $this->createClaim($item1, status: ClaimStatus::UNDER_REVIEW);

        $response = $this->actingAs($user, 'user')
            ->postJson(route('admin.lost_found.claims.approve', $claim->id), [
                'claimant_student_id' => $student2->id,
                'found_item_id' => $item2->id,
            ]);

        $response->assertStatus(200);

        $claim->refresh();
        $this->assertNotEquals($student2->id, $claim->claimant_student_id);
        $this->assertEquals($item1->id, $claim->found_item_id);
    }

    public function test_competing_claims_behavior_on_http_approval(): void
    {
        $user = $this->createEmployeeWithPermissions(['lost_found.claims.approve']);
        $item = $this->createFoundItem();
        $claimA = $this->createClaim($item, status: ClaimStatus::UNDER_REVIEW);
        $claimB = $this->createClaim($item, status: ClaimStatus::UNDER_REVIEW);

        $response = $this->actingAs($user, 'user')
            ->postJson(route('admin.lost_found.claims.approve', $claimA->id));

        $response->assertStatus(200);

        $claimA->refresh();
        $claimB->refresh();
        $item->refresh();

        $this->assertEquals(ClaimStatus::APPROVED, $claimA->status);
        $this->assertEquals(ClaimStatus::REJECTED, $claimB->status);
        $this->assertEquals($claimA->id, $item->approved_claim_id);
    }

    public function test_second_approval_attempt_fails(): void
    {
        $user = $this->createEmployeeWithPermissions(['lost_found.claims.approve']);
        $item = $this->createFoundItem();
        $claimA = $this->createClaim($item, status: ClaimStatus::UNDER_REVIEW);
        $claimB = $this->createClaim($item, status: ClaimStatus::UNDER_REVIEW);

        $this->actingAs($user, 'user')
            ->postJson(route('admin.lost_found.claims.approve', $claimA->id));

        $response = $this->actingAs($user, 'user')
            ->postJson(route('admin.lost_found.claims.approve', $claimB->id));

        $this->assertNotEquals(200, $response->status());

        $item->refresh();
        $this->assertEquals($claimA->id, $item->approved_claim_id);
    }

    public function test_approval_does_not_mutate_custody_or_return_item(): void
    {
        $user = $this->createEmployeeWithPermissions(['lost_found.claims.approve']);
        $item = $this->createFoundItem();
        $claim = $this->createClaim($item, status: ClaimStatus::UNDER_REVIEW);

        $response = $this->actingAs($user, 'user')
            ->postJson(route('admin.lost_found.claims.approve', $claim->id));

        $response->assertStatus(200);

        $item->refresh();
        $this->assertEquals(ItemStatus::REPORTED, $item->status);
        $this->assertNull($item->current_custodian_user_id);
    }

    public function test_post_handover_revocation_is_rejected(): void
    {
        $user = $this->createEmployeeWithPermissions(['lost_found.claims.approve']);
        $item = $this->createFoundItem();
        $claim = $this->createClaim($item, status: ClaimStatus::UNDER_REVIEW);

        // Approve
        $this->actingAs($user, 'user')
            ->postJson(route('admin.lost_found.claims.approve', $claim->id));

        $item->refresh();
        $item->status = ItemStatus::RETURNED;
        $item->save();

        Handover::create([
            'found_item_id' => $item->id,
            'claim_id' => $claim->id,
            'recipient_student_id' => $claim->claimant_student_id,
            'staff_user_id' => $user->id,
            'verification_method' => 'STUDENT_ID_CARD',
            'handed_over_at' => now(),
        ]);

        $response = $this->actingAs($user, 'user')
            ->postJson(route('admin.lost_found.claims.revoke', $claim->id));

        $this->assertNotEquals(200, $response->status());

        $item->refresh();
        $this->assertEquals(ItemStatus::RETURNED, $item->status);
        $this->assertEquals($claim->id, $item->approved_claim_id);
    }

    public function test_direct_application_service_authorization_checks(): void
    {
        $user = $this->createEmployeeWithPermissions(['unrelated.permission']);
        $item = $this->createFoundItem();
        $claim = $this->createClaim($item, status: ClaimStatus::UNDER_REVIEW);

        /** @var EmployeeClaimApplicationService $service */
        $service = app(EmployeeClaimApplicationService::class);

        $this->expectException(AuthorizationException::class);
        $service->approveClaim($user, $claim);
    }
}
