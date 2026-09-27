<?php

namespace Tests\Feature\LostAndFound;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;
use Webkul\LostAndFound\Enums\ClaimStatus;
use Webkul\LostAndFound\Enums\EvidenceType;
use Webkul\LostAndFound\Enums\FoundItemImageVisibility;
use Webkul\LostAndFound\Enums\ItemStatus;
use Webkul\LostAndFound\Enums\ReportStatus;
use Webkul\LostAndFound\Models\LostFoundCategory;
use Webkul\LostAndFound\Services\Application\EmployeeCategoryApplicationService;
use Webkul\LostAndFound\Services\Application\EmployeeClaimApplicationService;
use Webkul\LostAndFound\Services\Application\EmployeeCustodyApplicationService;
use Webkul\LostAndFound\Services\Application\EmployeeHandoverApplicationService;
use Webkul\LostAndFound\Services\Application\EmployeeItemApplicationService;
use Webkul\LostAndFound\Services\Application\StudentClaimApplicationService;
use Webkul\LostAndFound\Services\Application\StudentReportApplicationService;
use Webkul\Student\Models\Student;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

class LostAndFoundAuthorizationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('lost_found_private');
    }

    protected function createEmployee(string|array $permissions = []): User
    {
        if (is_string($permissions) && $permissions === 'all') {
            $role = Role::create([
                'name' => 'Super Admin '.Str::random(6),
                'permission_type' => 'all',
            ]);
        } else {
            $role = Role::create([
                'name' => 'Custom Role '.Str::random(6),
                'permission_type' => 'custom',
                'permissions' => (array) $permissions,
            ]);
        }

        return User::create([
            'name' => 'Test Employee '.Str::random(6),
            'email' => Str::random(10).'@example.test',
            'password' => Hash::make('password'),
            'status' => true,
            'role_id' => $role->id,
        ]);
    }

    protected function createStudent(): Student
    {
        return Student::create([
            'university_card_number' => 'STU-'.Str::random(8),
            'name' => 'Test Student '.Str::random(6),
            'password' => Hash::make('password'),
        ]);
    }

    protected function createCategory(): LostFoundCategory
    {
        return LostFoundCategory::create([
            'code' => 'cat-'.Str::random(6),
            'sort_order' => 1,
            'is_active' => true,
        ]);
    }

    protected function createSampleJpeg(): UploadedFile
    {
        return UploadedFile::fake()->image('test_sample.jpg', 200, 200);
    }

    public function test_authorized_employee_can_create_and_update_found_item(): void
    {
        $employee = $this->createEmployee(['lost_found.items.create', 'lost_found.items.edit']);
        $category = $this->createCategory();
        $service = app(EmployeeItemApplicationService::class);

        $item = $service->createFoundItem($employee, [
            'public_reference' => 'ITEM-'.Str::random(8),
            'category_id' => $category->id,
            'status' => ItemStatus::REPORTED->value,
            'title' => 'Found Keys',
        ]);

        $this->assertEquals($employee->id, $item->logged_by_user_id);
        $this->assertEquals('Found Keys', $item->title);

        $updated = $service->updateFoundItem($employee, $item->id, [
            'title' => 'Found Keys on Desk',
        ]);

        $this->assertEquals('Found Keys on Desk', $updated->title);
    }

    public function test_unauthorized_employee_cannot_create_found_item(): void
    {
        $employee = $this->createEmployee(['lost_found.items.view']);
        $category = $this->createCategory();
        $service = app(EmployeeItemApplicationService::class);

        $this->expectException(AuthorizationException::class);

        $service->createFoundItem($employee, [
            'public_reference' => 'ITEM-'.Str::random(8),
            'category_id' => $category->id,
            'status' => ItemStatus::REPORTED->value,
            'title' => 'Unauthorized Item',
        ]);
    }

    public function test_actor_identity_cannot_be_spoofed_in_found_item_creation(): void
    {
        $employee1 = $this->createEmployee('all');
        $employee2 = $this->createEmployee('all');
        $category = $this->createCategory();
        $service = app(EmployeeItemApplicationService::class);

        $item = $service->createFoundItem($employee1, [
            'public_reference' => 'ITEM-'.Str::random(8),
            'category_id' => $category->id,
            'logged_by_user_id' => $employee2->id,
            'status' => ItemStatus::REPORTED->value,
            'title' => 'Spoof Attempt Item',
        ]);

        $this->assertEquals($employee1->id, $item->logged_by_user_id);
    }

    public function test_unauthorized_employee_image_upload_fails_without_file_side_effects(): void
    {
        $employee = $this->createEmployee(['lost_found.items.view']);
        $admin = $this->createEmployee('all');
        $category = $this->createCategory();

        $itemService = app(EmployeeItemApplicationService::class);
        $item = $itemService->createFoundItem($admin, [
            'public_reference' => 'ITEM-'.Str::random(8),
            'category_id' => $category->id,
            'status' => ItemStatus::REPORTED->value,
            'title' => 'Item Image Test',
        ]);

        $this->expectException(AuthorizationException::class);

        try {
            $itemService->addFoundItemImage(
                $employee,
                $item,
                FoundItemImageVisibility::PUBLIC_SAFE,
                $this->createSampleJpeg()
            );
        } finally {
            $this->assertEquals(0, $item->images()->count());
            $this->assertEmpty(Storage::disk('public')->allFiles());
            $this->assertEmpty(Storage::disk('lost_found_private')->allFiles());
        }
    }

    public function test_employee_custody_operations_require_custody_manage_permission(): void
    {
        $custodian1 = $this->createEmployee('all');
        $unauthorizedStaff = $this->createEmployee(['lost_found.items.view']);

        $category = $this->createCategory();
        $itemService = app(EmployeeItemApplicationService::class);
        $custodyAppService = app(EmployeeCustodyApplicationService::class);

        $item = $itemService->createFoundItem($custodian1, [
            'public_reference' => 'ITEM-'.Str::random(8),
            'category_id' => $category->id,
            'status' => ItemStatus::REPORTED->value,
            'title' => 'Custody Item',
        ]);

        $this->expectException(AuthorizationException::class);
        $custodyAppService->receiveItem($unauthorizedStaff, $item, 'Locker 101');
    }

    public function test_authorized_custody_reception_works(): void
    {
        $custodian = $this->createEmployee(['lost_found.custody.manage']);
        $admin = $this->createEmployee('all');
        $category = $this->createCategory();

        $item = app(EmployeeItemApplicationService::class)->createFoundItem($admin, [
            'public_reference' => 'ITEM-'.Str::random(8),
            'category_id' => $category->id,
            'status' => ItemStatus::REPORTED->value,
            'title' => 'Custody Receive Item',
        ]);

        $item = app(EmployeeCustodyApplicationService::class)->receiveItem(
            $custodian,
            $item,
            'Vault A'
        );

        $this->assertEquals(ItemStatus::IN_CUSTODY, $item->status);
        $this->assertEquals('Vault A', $item->current_storage_location);
    }

    public function test_student_can_create_and_update_own_lost_report(): void
    {
        $student = $this->createStudent();
        $category = $this->createCategory();
        $reportAppService = app(StudentReportApplicationService::class);

        $report = $reportAppService->createLostReport($student, [
            'public_reference' => 'REPORT-'.Str::random(8),
            'category_id' => $category->id,
            'status' => ReportStatus::DRAFT->value,
            'title' => 'Lost Backpack',
        ]);

        $this->assertEquals($student->id, $report->student_id);
        $this->assertEquals(ReportStatus::DRAFT, $report->status);

        $updated = $reportAppService->updateOwnLostReport($student, $report, [
            'title' => 'Lost Red Backpack',
        ]);

        $this->assertEquals('Lost Red Backpack', $updated->title);
    }

    public function test_student_cannot_update_another_students_lost_report(): void
    {
        $student1 = $this->createStudent();
        $student2 = $this->createStudent();
        $category = $this->createCategory();
        $reportAppService = app(StudentReportApplicationService::class);

        $report = $reportAppService->createLostReport($student1, [
            'public_reference' => 'REPORT-'.Str::random(8),
            'category_id' => $category->id,
            'status' => ReportStatus::DRAFT->value,
            'title' => 'Student 1 Report',
        ]);

        $this->expectException(AuthorizationException::class);

        $reportAppService->updateOwnLostReport($student2, $report, [
            'title' => 'Hacked Title',
        ]);
    }

    public function test_student_cross_account_image_upload_denied_without_side_effects(): void
    {
        $student1 = $this->createStudent();
        $student2 = $this->createStudent();
        $category = $this->createCategory();
        $reportAppService = app(StudentReportApplicationService::class);

        $report = $reportAppService->createLostReport($student1, [
            'public_reference' => 'REPORT-'.Str::random(8),
            'category_id' => $category->id,
            'status' => ReportStatus::DRAFT->value,
            'title' => 'Student 1 Image Report',
        ]);

        $this->expectException(AuthorizationException::class);

        try {
            $reportAppService->addOwnLostReportImage(
                $student2,
                $report,
                $this->createSampleJpeg()
            );
        } finally {
            $this->assertEquals(0, $report->images()->count());
            $this->assertEmpty(Storage::disk('lost_found_private')->allFiles());
        }
    }

    public function test_student_submit_claim_derives_claimant_id_from_actor(): void
    {
        $student1 = $this->createStudent();
        $student2 = $this->createStudent();
        $admin = $this->createEmployee('all');
        $category = $this->createCategory();

        $item = app(EmployeeItemApplicationService::class)->createFoundItem($admin, [
            'public_reference' => 'ITEM-'.Str::random(8),
            'category_id' => $category->id,
            'status' => ItemStatus::REPORTED->value,
            'title' => 'Item For Claim',
        ]);

        $claimAppService = app(StudentClaimApplicationService::class);
        $claim = $claimAppService->submitClaim($student1, $item, [
            'claimant_student_id' => $student2->id,
        ]);

        $this->assertEquals($student1->id, $claim->claimant_student_id);
        $this->assertEquals($item->id, $claim->found_item_id);
        $this->assertEquals(ClaimStatus::SUBMITTED, $claim->status);
    }

    public function test_student_cannot_add_evidence_or_withdraw_another_students_claim(): void
    {
        $student1 = $this->createStudent();
        $student2 = $this->createStudent();
        $admin = $this->createEmployee('all');
        $category = $this->createCategory();

        $item = app(EmployeeItemApplicationService::class)->createFoundItem($admin, [
            'public_reference' => 'ITEM-'.Str::random(8),
            'category_id' => $category->id,
            'status' => ItemStatus::REPORTED->value,
            'title' => 'Claim Evidence Item',
        ]);

        $claimAppService = app(StudentClaimApplicationService::class);
        $claim = $claimAppService->submitClaim($student1, $item);

        $this->expectException(AuthorizationException::class);
        $claimAppService->addOwnClaimEvidence($student2, $claim, EvidenceType::TEXT_DESCRIPTION, 'Unauthorized evidence');
    }

    public function test_employee_claim_approval_requires_approve_permission(): void
    {
        $admin = $this->createEmployee('all');
        $reviewerOnly = $this->createEmployee(['lost_found.claims.review']);
        $student = $this->createStudent();
        $category = $this->createCategory();

        $item = app(EmployeeItemApplicationService::class)->createFoundItem($admin, [
            'public_reference' => 'ITEM-'.Str::random(8),
            'category_id' => $category->id,
            'status' => ItemStatus::REPORTED->value,
            'title' => 'Approval Test Item',
        ]);

        $claim = app(StudentClaimApplicationService::class)->submitClaim($student, $item);
        $claimAppService = app(EmployeeClaimApplicationService::class);
        $claimAppService->reviewClaim($admin, $claim, ClaimStatus::UNDER_REVIEW);

        $this->expectException(AuthorizationException::class);
        $claimAppService->approveClaim($reviewerOnly, $claim, 'Attempted approval');
    }

    public function test_authorized_employee_can_approve_claim(): void
    {
        $admin = $this->createEmployee('all');
        $approver = $this->createEmployee(['lost_found.claims.approve', 'lost_found.claims.review']);
        $student = $this->createStudent();
        $category = $this->createCategory();

        $item = app(EmployeeItemApplicationService::class)->createFoundItem($admin, [
            'public_reference' => 'ITEM-'.Str::random(8),
            'category_id' => $category->id,
            'status' => ItemStatus::REPORTED->value,
            'title' => 'Approval Test Item 2',
        ]);

        $claim = app(StudentClaimApplicationService::class)->submitClaim($student, $item);
        $claimAppService = app(EmployeeClaimApplicationService::class);
        $claimAppService->reviewClaim($admin, $claim, ClaimStatus::UNDER_REVIEW);

        $approvedClaim = $claimAppService->approveClaim($approver, $claim, 'Approved by manager');

        $this->assertEquals(ClaimStatus::APPROVED, $approvedClaim->status);
        $this->assertEquals($claim->id, $item->fresh()->approved_claim_id);
    }

    public function test_employee_handover_requires_handover_complete_permission(): void
    {
        $admin = $this->createEmployee('all');
        $custodyStaff = $this->createEmployee(['lost_found.custody.manage']);
        $student = $this->createStudent();
        $category = $this->createCategory();

        $item = app(EmployeeItemApplicationService::class)->createFoundItem($admin, [
            'public_reference' => 'ITEM-'.Str::random(8),
            'category_id' => $category->id,
            'status' => ItemStatus::REPORTED->value,
            'title' => 'Handover Item',
        ]);

        app(EmployeeCustodyApplicationService::class)->receiveItem($admin, $item, 'Vault B');
        $claim = app(StudentClaimApplicationService::class)->submitClaim($student, $item);
        app(EmployeeClaimApplicationService::class)->reviewClaim($admin, $claim, ClaimStatus::UNDER_REVIEW);
        app(EmployeeClaimApplicationService::class)->approveClaim($admin, $claim);

        $handoverAppService = app(EmployeeHandoverApplicationService::class);

        $this->expectException(AuthorizationException::class);
        $handoverAppService->completeHandover($custodyStaff, $claim, $student, ['verification_method' => 'ID Card']);
    }

    public function test_authorized_employee_can_complete_handover(): void
    {
        $admin = $this->createEmployee('all');
        $handoverOfficer = $this->createEmployee(['lost_found.handover.complete']);
        $student = $this->createStudent();
        $category = $this->createCategory();

        $item = app(EmployeeItemApplicationService::class)->createFoundItem($admin, [
            'public_reference' => 'ITEM-'.Str::random(8),
            'category_id' => $category->id,
            'status' => ItemStatus::REPORTED->value,
            'title' => 'Handover Item 2',
        ]);

        app(EmployeeCustodyApplicationService::class)->receiveItem($admin, $item, 'Vault B');
        $claim = app(StudentClaimApplicationService::class)->submitClaim($student, $item);
        app(EmployeeClaimApplicationService::class)->reviewClaim($admin, $claim, ClaimStatus::UNDER_REVIEW);
        app(EmployeeClaimApplicationService::class)->approveClaim($admin, $claim);

        $handoverAppService = app(EmployeeHandoverApplicationService::class);

        $handover = $handoverAppService->completeHandover($handoverOfficer, $claim, $student, ['verification_method' => 'ID Card']);

        $this->assertNotNull($handover);
        $this->assertEquals(ItemStatus::RETURNED, $item->fresh()->status);
        $this->assertEquals($handoverOfficer->id, $handover->staff_user_id);
    }

    public function test_category_management_requires_categories_permission(): void
    {
        $unauthorized = $this->createEmployee(['lost_found.items.view']);
        $categoryAppService = app(EmployeeCategoryApplicationService::class);

        $this->expectException(AuthorizationException::class);
        $categoryAppService->createCategory($unauthorized, [
            'code' => 'new-cat',
        ]);
    }
}
