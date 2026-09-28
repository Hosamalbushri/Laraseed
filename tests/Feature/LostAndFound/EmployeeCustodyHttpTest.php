<?php

namespace Tests\Feature\LostAndFound;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;
use Webkul\LostAndFound\Enums\ClaimStatus;
use Webkul\LostAndFound\Enums\CustodyEventType;
use Webkul\LostAndFound\Enums\ItemStatus;
use Webkul\LostAndFound\Models\CustodyRecord;
use Webkul\LostAndFound\Models\FoundItem;
use Webkul\LostAndFound\Models\Handover;
use Webkul\LostAndFound\Models\LostFoundCategory;
use Webkul\LostAndFound\Models\LostFoundClaim;
use Webkul\LostAndFound\Services\Application\EmployeeCustodyApplicationService;
use Webkul\Student\Models\Student;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

class EmployeeCustodyHttpTest extends TestCase
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

    protected function createEmployeeWithPermissions(array $permissions, bool $active = true): User
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
            'status' => $active ? 1 : 0,
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

    public function test_unauthenticated_request_returns_redirect(): void
    {
        $item = $this->createFoundItem();

        $htmlResp = $this->post(route('admin.lost_found.custody.receive', $item->id));
        $this->assertEquals(302, $htmlResp->status());

        $jsonResp = $this->postJson(route('admin.lost_found.custody.receive', $item->id));
        $this->assertEquals(302, $jsonResp->status());
    }

    public function test_student_guard_user_cannot_access_employee_custody_endpoints(): void
    {
        $student = $this->createStudent();
        $item = $this->createFoundItem();

        $response = $this->actingAs($student, 'student')
            ->postJson(route('admin.lost_found.custody.receive', $item->id), [
                'storage_location' => 'Locker 1',
            ]);

        $this->assertEquals(302, $response->status());
    }

    public function test_employee_without_permission_is_denied(): void
    {
        $user = $this->createEmployeeWithPermissions(['unrelated.permission']);
        $item = $this->createFoundItem();

        $response = $this->actingAs($user, 'user')
            ->postJson(route('admin.lost_found.custody.receive', $item->id), [
                'storage_location' => 'Locker 1',
            ]);

        $this->assertEquals(401, $response->status());
    }

    public function test_unrelated_permission_cannot_access_custody_operations(): void
    {
        $user = $this->createEmployeeWithPermissions(['lost_found.items.edit', 'lost_found.claims.approve']);
        $item = $this->createFoundItem();

        $receiveResp = $this->actingAs($user, 'user')
            ->postJson(route('admin.lost_found.custody.receive', $item->id), ['storage_location' => 'Locker A']);
        $this->assertEquals(401, $receiveResp->status());

        $transferResp = $this->actingAs($user, 'user')
            ->postJson(route('admin.lost_found.custody.transfer', $item->id), ['to_custodian_user_id' => $user->id, 'to_storage_location' => 'Locker B']);
        $this->assertEquals(401, $transferResp->status());

        $moveResp = $this->actingAs($user, 'user')
            ->postJson(route('admin.lost_found.custody.move_storage', $item->id), ['to_storage_location' => 'Locker C']);
        $this->assertEquals(401, $moveResp->status());
    }

    public function test_authenticated_employee_with_custody_permission_can_receive_custody(): void
    {
        $user = $this->createEmployeeWithPermissions(['lost_found.custody.manage']);
        $item = $this->createFoundItem();

        $response = $this->actingAs($user, 'user')
            ->postJson(route('admin.lost_found.custody.receive', $item->id), [
                'storage_location' => 'Vault A-1',
                'notes' => 'Logged into secure storage.',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', ItemStatus::IN_CUSTODY->value)
            ->assertJsonPath('data.current_custodian_user_id', $user->id)
            ->assertJsonPath('data.current_storage_location', 'Vault A-1');

        $item->refresh();
        $this->assertEquals(ItemStatus::IN_CUSTODY, $item->status);
        $this->assertEquals($user->id, $item->current_custodian_user_id);
        $this->assertEquals('Vault A-1', $item->current_storage_location);

        $record = CustodyRecord::where('found_item_id', $item->id)->latest('id')->first();
        $this->assertNotNull($record);
        $this->assertEquals(CustodyEventType::LOGGED, $record->event_type);
        $this->assertEquals($user->id, $record->actor_user_id);
        $this->assertNull($record->from_custodian_user_id);
        $this->assertEquals($user->id, $record->to_custodian_user_id);
        $this->assertNull($record->from_storage_location);
        $this->assertEquals('Vault A-1', $record->to_storage_location);
    }

    public function test_authenticated_employee_with_custody_permission_can_transfer_custody(): void
    {
        $staff1 = $this->createEmployeeWithPermissions(['lost_found.custody.manage']);
        $staff2 = $this->createEmployeeWithPermissions(['lost_found.custody.manage']);
        $item = $this->createFoundItem();

        // Staff1 receives custody
        $this->actingAs($staff1, 'user')
            ->postJson(route('admin.lost_found.custody.receive', $item->id), [
                'storage_location' => 'Vault A-1',
            ]);

        // Staff1 transfers custody to Staff2 at Vault B-2
        $response = $this->actingAs($staff1, 'user')
            ->postJson(route('admin.lost_found.custody.transfer', $item->id), [
                'to_custodian_user_id' => $staff2->id,
                'to_storage_location' => 'Vault B-2',
                'notes' => 'Transferring shift custody.',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.current_custodian_user_id', $staff2->id)
            ->assertJsonPath('data.current_storage_location', 'Vault B-2');

        $item->refresh();
        $this->assertEquals($staff2->id, $item->current_custodian_user_id);
        $this->assertEquals('Vault B-2', $item->current_storage_location);

        $record = CustodyRecord::where('found_item_id', $item->id)->latest('id')->first();
        $this->assertNotNull($record);
        $this->assertEquals(CustodyEventType::TRANSFERRED, $record->event_type);
        $this->assertEquals($staff1->id, $record->actor_user_id);
        $this->assertEquals($staff1->id, $record->from_custodian_user_id);
        $this->assertEquals($staff2->id, $record->to_custodian_user_id);
        $this->assertEquals('Vault A-1', $record->from_storage_location);
        $this->assertEquals('Vault B-2', $record->to_storage_location);
    }

    public function test_authenticated_employee_with_custody_permission_can_move_storage_location(): void
    {
        $staff = $this->createEmployeeWithPermissions(['lost_found.custody.manage']);
        $item = $this->createFoundItem();

        $this->actingAs($staff, 'user')
            ->postJson(route('admin.lost_found.custody.receive', $item->id), [
                'storage_location' => 'Vault A-1',
            ]);

        $response = $this->actingAs($staff, 'user')
            ->postJson(route('admin.lost_found.custody.move_storage', $item->id), [
                'to_storage_location' => 'Shelf C-3',
                'notes' => 'Re-organizing storage shelves.',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.current_custodian_user_id', $staff->id)
            ->assertJsonPath('data.current_storage_location', 'Shelf C-3');

        $item->refresh();
        $this->assertEquals($staff->id, $item->current_custodian_user_id);
        $this->assertEquals('Shelf C-3', $item->current_storage_location);

        $record = CustodyRecord::where('found_item_id', $item->id)->latest('id')->first();
        $this->assertNotNull($record);
        $this->assertEquals(CustodyEventType::STORAGE_LOCATION_CHANGED, $record->event_type);
        $this->assertEquals($staff->id, $record->actor_user_id);
        $this->assertEquals($staff->id, $record->from_custodian_user_id);
        $this->assertEquals($staff->id, $record->to_custodian_user_id);
        $this->assertEquals('Vault A-1', $record->from_storage_location);
        $this->assertEquals('Shelf C-3', $record->to_storage_location);
    }

    public function test_actor_user_id_spoofing_in_payload_is_ignored(): void
    {
        $staff1 = $this->createEmployeeWithPermissions(['lost_found.custody.manage']);
        $staff2 = $this->createSuperAdminUser();
        $item = $this->createFoundItem();

        $response = $this->actingAs($staff1, 'user')
            ->postJson(route('admin.lost_found.custody.receive', $item->id), [
                'storage_location' => 'Vault A-1',
                'actor_user_id' => $staff2->id,
                'from_custodian_user_id' => $staff2->id,
                'user_id' => $staff2->id,
            ]);

        $response->assertStatus(200);

        $record = CustodyRecord::where('found_item_id', $item->id)->latest('id')->first();
        $this->assertNotNull($record);
        $this->assertEquals($staff1->id, $record->actor_user_id);
    }

    public function test_duplicate_receive_fails(): void
    {
        $staff = $this->createEmployeeWithPermissions(['lost_found.custody.manage']);
        $item = $this->createFoundItem();

        $this->actingAs($staff, 'user')
            ->postJson(route('admin.lost_found.custody.receive', $item->id), ['storage_location' => 'Vault 1'])
            ->assertStatus(200);

        $initialCount = CustodyRecord::where('found_item_id', $item->id)->count();

        // Second receive attempt
        $secondResp = $this->actingAs($staff, 'user')
            ->postJson(route('admin.lost_found.custody.receive', $item->id), ['storage_location' => 'Vault 2']);

        $secondResp->assertStatus(422);

        $this->assertEquals($initialCount, CustodyRecord::where('found_item_id', $item->id)->count());
    }

    public function test_stale_transfer_attempt_is_rejected(): void
    {
        $staff1 = $this->createEmployeeWithPermissions(['lost_found.custody.manage']);
        $staff2 = $this->createEmployeeWithPermissions(['lost_found.custody.manage']);
        $staff3 = $this->createEmployeeWithPermissions(['lost_found.custody.manage']);
        $item = $this->createFoundItem();

        // Staff1 receives custody
        $this->actingAs($staff1, 'user')
            ->postJson(route('admin.lost_found.custody.receive', $item->id), ['storage_location' => 'Vault 1']);

        // Staff1 transfers to Staff2
        $this->actingAs($staff1, 'user')
            ->postJson(route('admin.lost_found.custody.transfer', $item->id), [
                'to_custodian_user_id' => $staff2->id,
                'to_storage_location' => 'Vault 2',
            ])->assertStatus(200);

        // Stale transfer attempt expecting Staff1 at Vault 1 to transfer to Staff3
        $staleResp = $this->actingAs($staff1, 'user')
            ->postJson(route('admin.lost_found.custody.transfer', $item->id), [
                'to_custodian_user_id' => $staff3->id,
                'to_storage_location' => 'Vault 3',
                'expected_custodian_user_id' => $staff1->id,
                'expected_storage_location' => 'Vault 1',
            ]);

        $staleResp->assertStatus(422);

        $item->refresh();
        $this->assertEquals($staff2->id, $item->current_custodian_user_id);
        $this->assertEquals('Vault 2', $item->current_storage_location);
    }

    public function test_transfer_to_inactive_custodian_fails(): void
    {
        $staff1 = $this->createEmployeeWithPermissions(['lost_found.custody.manage']);
        $inactiveStaff = $this->createEmployeeWithPermissions(['lost_found.custody.manage'], active: false);
        $item = $this->createFoundItem();

        $this->actingAs($staff1, 'user')
            ->postJson(route('admin.lost_found.custody.receive', $item->id), ['storage_location' => 'Vault 1']);

        $response = $this->actingAs($staff1, 'user')
            ->postJson(route('admin.lost_found.custody.transfer', $item->id), [
                'to_custodian_user_id' => $inactiveStaff->id,
                'to_storage_location' => 'Vault 2',
            ]);

        $response->assertStatus(422);
    }

    public function test_transfer_to_nonexistent_custodian_fails(): void
    {
        $staff = $this->createEmployeeWithPermissions(['lost_found.custody.manage']);
        $item = $this->createFoundItem();

        $this->actingAs($staff, 'user')
            ->postJson(route('admin.lost_found.custody.receive', $item->id), ['storage_location' => 'Vault 1']);

        $response = $this->actingAs($staff, 'user')
            ->postJson(route('admin.lost_found.custody.transfer', $item->id), [
                'to_custodian_user_id' => 999999,
                'to_storage_location' => 'Vault 2',
            ]);

        $response->assertStatus(422);
    }

    public function test_no_op_storage_move_is_rejected(): void
    {
        $staff = $this->createEmployeeWithPermissions(['lost_found.custody.manage']);
        $item = $this->createFoundItem();

        $this->actingAs($staff, 'user')
            ->postJson(route('admin.lost_found.custody.receive', $item->id), ['storage_location' => 'Vault 1']);

        $response = $this->actingAs($staff, 'user')
            ->postJson(route('admin.lost_found.custody.move_storage', $item->id), [
                'to_storage_location' => 'Vault 1',
            ]);

        $response->assertStatus(422);
    }

    public function test_returned_item_custody_operation_is_rejected(): void
    {
        $staff = $this->createEmployeeWithPermissions(['lost_found.custody.manage']);
        $item = $this->createFoundItem();
        $item->status = ItemStatus::RETURNED;
        $item->save();

        $response = $this->actingAs($staff, 'user')
            ->postJson(route('admin.lost_found.custody.receive', $item->id), ['storage_location' => 'Vault 1']);

        $response->assertStatus(422);
    }

    public function test_custody_operations_do_not_mutate_claims_or_approved_claim_id(): void
    {
        $staff = $this->createEmployeeWithPermissions(['lost_found.custody.manage']);
        $item = $this->createFoundItem();
        $student = $this->createStudent();
        $claim = LostFoundClaim::create([
            'found_item_id' => $item->id,
            'claimant_student_id' => $student->id,
            'status' => ClaimStatus::SUBMITTED,
            'submitted_at' => now(),
        ]);

        $this->actingAs($staff, 'user')
            ->postJson(route('admin.lost_found.custody.receive', $item->id), ['storage_location' => 'Vault 1'])
            ->assertStatus(200);

        $claim->refresh();
        $item->refresh();

        $this->assertEquals(ClaimStatus::SUBMITTED, $claim->status);
        $this->assertNull($item->approved_claim_id);
    }

    public function test_post_handover_custody_operation_is_rejected(): void
    {
        $staff = $this->createEmployeeWithPermissions(['lost_found.custody.manage']);
        $item = $this->createFoundItem();
        $student = $this->createStudent();
        $claim = LostFoundClaim::create([
            'found_item_id' => $item->id,
            'claimant_student_id' => $student->id,
            'status' => ClaimStatus::APPROVED,
            'submitted_at' => now(),
        ]);

        $item->status = ItemStatus::RETURNED;
        $item->save();

        Handover::create([
            'found_item_id' => $item->id,
            'claim_id' => $claim->id,
            'recipient_student_id' => $student->id,
            'staff_user_id' => $staff->id,
            'verification_method' => 'STUDENT_ID_CARD',
            'handed_over_at' => now(),
        ]);

        $response = $this->actingAs($staff, 'user')
            ->postJson(route('admin.lost_found.custody.receive', $item->id), ['storage_location' => 'Vault 1']);

        $response->assertStatus(422);
    }

    public function test_custody_operations_never_create_handover_records_or_handed_over_events(): void
    {
        $staff = $this->createEmployeeWithPermissions(['lost_found.custody.manage']);
        $item = $this->createFoundItem();

        $this->actingAs($staff, 'user')
            ->postJson(route('admin.lost_found.custody.receive', $item->id), ['storage_location' => 'Vault 1'])
            ->assertStatus(200);

        $this->assertEquals(0, Handover::where('found_item_id', $item->id)->count());
        $this->assertEquals(0, CustodyRecord::where('found_item_id', $item->id)->where('event_type', CustodyEventType::HANDED_OVER)->count());
    }

    public function test_direct_application_service_authorization_checks(): void
    {
        $user = $this->createEmployeeWithPermissions(['unrelated.permission']);
        $item = $this->createFoundItem();

        /** @var EmployeeCustodyApplicationService $service */
        $service = app(EmployeeCustodyApplicationService::class);

        $this->expectException(AuthorizationException::class);
        $service->receiveItem($user, $item, 'Vault 1');
    }
}
