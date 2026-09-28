<?php

namespace Tests\Feature\LostAndFound;

use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;
use Webkul\LostAndFound\Enums\ClaimStatus;
use Webkul\LostAndFound\Enums\CustodyEventType;
use Webkul\LostAndFound\Enums\FoundItemImageVisibility;
use Webkul\LostAndFound\Enums\ItemStatus;
use Webkul\LostAndFound\Models\FoundItem;
use Webkul\LostAndFound\Models\Handover;
use Webkul\LostAndFound\Models\LostFoundCategory;
use Webkul\LostAndFound\Models\LostFoundClaim;
use Webkul\LostAndFound\Repositories\LostFoundClaimRepository;
use Webkul\LostAndFound\Services\Application\EmployeeHandoverApplicationService;
use Webkul\LostAndFound\Services\ClaimResolutionService;
use Webkul\LostAndFound\Services\CustodyService;
use Webkul\Student\Models\Student;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

class EmployeeHandoverHttpTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('lost_found_private');
    }

    protected function createEmployee(array|string $permissions = [], bool $active = true): User
    {
        $role = Role::create([
            'name' => 'Handover HTTP Role '.Str::random(8),
            'permission_type' => $permissions === 'all' ? 'all' : 'custom',
            'permissions' => $permissions === 'all' ? null : $permissions,
        ]);

        return User::create([
            'name' => 'Handover HTTP Staff '.Str::random(8),
            'email' => Str::random(12).'@example.test',
            'password' => Hash::make('password'),
            'status' => $active,
            'role_id' => $role->id,
        ]);
    }

    protected function createStudent(): Student
    {
        return Student::create([
            'university_card_number' => 'HANDOVER-HTTP-'.Str::random(10),
            'name' => 'Handover HTTP Student',
            'password' => Hash::make('password'),
        ]);
    }

    protected function createItem(User $logger): FoundItem
    {
        $category = LostFoundCategory::create([
            'code' => 'handover-http-'.Str::random(10),
            'is_active' => true,
        ]);

        return FoundItem::create([
            'public_reference' => 'HANDOVER-HTTP-'.Str::random(10),
            'category_id' => $category->id,
            'logged_by_user_id' => $logger->id,
            'status' => ItemStatus::REPORTED,
            'title' => 'Handover HTTP item',
            'found_at' => now(),
            'reported_at' => now(),
        ]);
    }

    protected function createApprovedFixture(
        array|string $actorPermissions = ['lost_found.handover.complete'],
        bool $withCustody = true,
    ): array {
        $admin = $this->createEmployee('all');
        $actor = $this->createEmployee($actorPermissions);
        $student = $this->createStudent();
        $item = $this->createItem($admin);

        if ($withCustody) {
            app(CustodyService::class)->receive(
                $item->id,
                $admin->id,
                $admin->id,
                'Secure handover cabinet',
                now()->subMinute(),
            );
        }

        $claim = app(LostFoundClaimRepository::class)->create([
            'found_item_id' => $item->id,
            'claimant_student_id' => $student->id,
        ]);
        $claim = app(ClaimResolutionService::class)->review(
            $item->id,
            $claim->id,
            $admin->id,
            ClaimStatus::UNDER_REVIEW,
        );
        $claim = app(ClaimResolutionService::class)->approve($item->id, $claim->id, $admin->id);

        return [$item->fresh(), $claim, $student, $actor, $admin];
    }

    protected function completeUrl(FoundItem $item): string
    {
        return route('admin.lost_found.handover.complete', $item->id);
    }

    protected function validPayload(): array
    {
        return [
            'verification_method' => 'visual university card inspection',
            'verification_note' => 'Card details matched the approved claimant.',
        ];
    }

    public function test_successful_handover_has_atomic_terminal_effects_and_minimal_response(): void
    {
        [$item, $claim, $student, $actor, $custodian] = $this->createApprovedFixture();
        $historyBefore = $item->custodyRecords()->pluck('id')->all();

        $response = $this->actingAs($actor, 'user')->postJson($this->completeUrl($item), $this->validPayload());

        $response->assertOk()
            ->assertJsonPath('data.found_item_id', $item->id)
            ->assertJsonPath('data.item_status', ItemStatus::RETURNED->value)
            ->assertJsonMissingPath('data.staff_user_id')
            ->assertJsonMissingPath('data.recipient_student_id')
            ->assertJsonMissingPath('data.claim_id')
            ->assertJsonMissingPath('data.verification_method')
            ->assertJsonMissingPath('data.verification_note');

        $handover = Handover::query()->sole();
        $terminalEvent = $item->custodyRecords()
            ->where('event_type', CustodyEventType::HANDED_OVER->value)
            ->sole();
        $item->refresh();
        $claim->refresh();

        $responseKeys = array_keys($response->json('data'));
        sort($responseKeys);
        $this->assertSame(['found_item_id', 'handed_over_at', 'id', 'item_status'], $responseKeys);
        $this->assertSame($claim->id, $handover->claim_id);
        $this->assertSame($student->id, $handover->recipient_student_id);
        $this->assertSame($actor->id, $handover->staff_user_id);
        $this->assertSame($actor->id, $terminalEvent->actor_user_id);
        $this->assertSame($custodian->id, $terminalEvent->from_custodian_user_id);
        $this->assertNull($terminalEvent->to_custodian_user_id);
        $this->assertNull($terminalEvent->to_storage_location);
        $this->assertSame($historyBefore, $item->custodyRecords()->whereKey($historyBefore)->pluck('id')->all());
        $this->assertSame(ItemStatus::RETURNED, $item->status);
        $this->assertSame($claim->id, $item->approved_claim_id);
        $this->assertSame(ClaimStatus::APPROVED, $claim->status);
        $this->assertNull($item->current_custodian_user_id);
        $this->assertNull($item->current_storage_location);
        $this->assertNull($item->custody_started_at);
        $this->assertNull($item->custody_changed_at);
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertSame([], Storage::disk('lost_found_private')->allFiles());
    }

    public function test_unauthenticated_html_request_redirects(): void
    {
        [$item] = $this->createApprovedFixture();

        $this->post($this->completeUrl($item), $this->validPayload())->assertStatus(302);
        $this->assertSame(0, Handover::query()->count());
    }

    public function test_unauthenticated_json_request_redirects(): void
    {
        [$item] = $this->createApprovedFixture();

        $this->postJson($this->completeUrl($item), $this->validPayload())->assertStatus(302);
        $this->assertSame(0, Handover::query()->count());
    }

    public function test_student_guard_cannot_access_employee_handover(): void
    {
        [$item, , $student] = $this->createApprovedFixture();

        $this->actingAs($student, 'student')
            ->postJson($this->completeUrl($item), $this->validPayload())
            ->assertStatus(302);
        $this->assertSame(0, Handover::query()->count());
    }

    public function test_employee_without_permission_is_denied_without_side_effects(): void
    {
        [$item] = $this->createApprovedFixture(['unrelated.permission']);

        $this->actingAs($this->createEmployee(['unrelated.permission']), 'user')
            ->postJson($this->completeUrl($item), $this->validPayload())
            ->assertStatus(401);

        $this->assertSame(0, Handover::query()->count());
        $this->assertSame(ItemStatus::IN_CUSTODY, $item->fresh()->status);
        $this->assertSame(0, $item->custodyRecords()->where('event_type', CustodyEventType::HANDED_OVER->value)->count());
    }

    public function test_items_edit_only_is_denied(): void
    {
        $this->assertIsolatedPermissionDenied('lost_found.items.edit');
    }

    public function test_custody_manage_only_is_denied(): void
    {
        $this->assertIsolatedPermissionDenied('lost_found.custody.manage');
    }

    public function test_claims_approve_only_is_denied(): void
    {
        $this->assertIsolatedPermissionDenied('lost_found.claims.approve');
    }

    protected function assertIsolatedPermissionDenied(string $permission): void
    {
        [$item, , , $actor] = $this->createApprovedFixture([$permission]);

        $this->actingAs($actor, 'user')
            ->postJson($this->completeUrl($item), $this->validPayload())
            ->assertStatus(401);
        $this->assertSame(0, Handover::query()->count());
        $this->assertSame(ItemStatus::IN_CUSTODY, $item->fresh()->status);
    }

    public function test_direct_application_service_denies_unauthorized_employee_before_domain_effects(): void
    {
        [$item] = $this->createApprovedFixture();
        $unauthorized = $this->createEmployee(['lost_found.custody.manage']);

        try {
            app(EmployeeHandoverApplicationService::class)->completeHandover(
                $unauthorized,
                $item,
                $this->validPayload(),
            );
            $this->fail('Expected application authorization to fail.');
        } catch (AuthorizationException) {
            $this->assertSame(0, Handover::query()->count());
            $this->assertSame(ItemStatus::IN_CUSTODY, $item->fresh()->status);
        }
    }

    public function test_staff_spoofing_is_ignored_and_authenticated_employee_is_persisted(): void
    {
        [$item, , , $actor] = $this->createApprovedFixture();
        $other = $this->createEmployee('all');

        $this->actingAs($actor, 'user')->postJson($this->completeUrl($item), [
            ...$this->validPayload(),
            'staff_user_id' => $other->id,
            'user_id' => $other->id,
            'actor_user_id' => $other->id,
            'handed_over_by' => $other->id,
        ])->assertOk();

        $this->assertSame($actor->id, Handover::query()->sole()->staff_user_id);
    }

    public function test_recipient_and_claim_spoofing_cannot_select_handover_authorities(): void
    {
        [$item, $claim, $student, $actor] = $this->createApprovedFixture();
        $otherStudent = $this->createStudent();
        $otherItem = $this->createItem($actor);
        $otherClaim = LostFoundClaim::create([
            'found_item_id' => $otherItem->id,
            'claimant_student_id' => $otherStudent->id,
            'status' => ClaimStatus::SUBMITTED,
            'submitted_at' => now(),
        ]);

        $this->actingAs($actor, 'user')->postJson($this->completeUrl($item), [
            ...$this->validPayload(),
            'recipient_student_id' => $otherStudent->id,
            'claimant_student_id' => $otherStudent->id,
            'claim_id' => $otherClaim->id,
            'approved_claim_id' => $otherClaim->id,
        ])->assertOk();

        $handover = Handover::query()->sole();
        $this->assertSame($student->id, $handover->recipient_student_id);
        $this->assertSame($claim->id, $handover->claim_id);
        $this->assertSame(ItemStatus::REPORTED, $otherItem->fresh()->status);
    }

    public function test_no_approved_claim_is_rejected_without_terminal_effects(): void
    {
        $admin = $this->createEmployee('all');
        $actor = $this->createEmployee(['lost_found.handover.complete']);
        $item = $this->createItem($admin);
        app(CustodyService::class)->receive($item->id, $admin->id, $admin->id, 'No claim cabinet');

        $this->actingAs($actor, 'user')->postJson($this->completeUrl($item), $this->validPayload())->assertStatus(422);
        $this->assertSame(0, Handover::query()->count());
        $this->assertSame(ItemStatus::IN_CUSTODY, $item->fresh()->status);
        $this->assertSame(1, $item->custodyRecords()->count());
    }

    public function test_approved_claim_without_custody_is_rejected_without_terminal_effects(): void
    {
        [$item, $claim, , $actor] = $this->createApprovedFixture(withCustody: false);

        $this->actingAs($actor, 'user')->postJson($this->completeUrl($item), $this->validPayload())->assertStatus(422);
        $this->assertSame(0, Handover::query()->count());
        $this->assertSame(ItemStatus::REPORTED, $item->fresh()->status);
        $this->assertSame($claim->id, $item->approved_claim_id);
        $this->assertSame(0, $item->custodyRecords()->count());
    }

    public function test_non_approved_authoritative_claim_is_rejected(): void
    {
        [$item, $claim, , $actor] = $this->createApprovedFixture();
        DB::table('lost_found_claims')->where('id', $claim->id)->update(['status' => ClaimStatus::UNDER_REVIEW->value]);

        $this->actingAs($actor, 'user')->postJson($this->completeUrl($item), $this->validPayload())->assertStatus(422);
        $this->assertSame(0, Handover::query()->count());
        $this->assertSame(ItemStatus::IN_CUSTODY, $item->fresh()->status);
    }

    public function test_inconsistent_custody_projection_is_rejected(): void
    {
        [$item, , , $actor] = $this->createApprovedFixture();
        $otherCustodian = $this->createEmployee('all');
        DB::table('lost_found_items')->where('id', $item->id)->update([
            'current_custodian_user_id' => $otherCustodian->id,
        ]);

        $this->actingAs($actor, 'user')->postJson($this->completeUrl($item), $this->validPayload())->assertStatus(422);
        $this->assertSame(0, Handover::query()->count());
        $this->assertSame(ItemStatus::IN_CUSTODY, $item->fresh()->status);
        $this->assertSame(1, $item->custodyRecords()->count());
    }

    public function test_duplicate_http_handover_is_rejected_exactly_once(): void
    {
        [$item, , , $actor] = $this->createApprovedFixture();

        $this->actingAs($actor, 'user')->postJson($this->completeUrl($item), $this->validPayload())->assertOk();
        $this->actingAs($actor, 'user')->postJson($this->completeUrl($item), $this->validPayload())->assertStatus(422);

        $this->assertSame(1, Handover::query()->where('found_item_id', $item->id)->count());
        $this->assertSame(1, $item->custodyRecords()->where('event_type', CustodyEventType::HANDED_OVER->value)->count());
        $this->assertSame(ItemStatus::RETURNED, $item->fresh()->status);
    }

    public function test_verification_method_is_required(): void
    {
        [$item, , , $actor] = $this->createApprovedFixture();

        $this->actingAs($actor, 'user')->postJson($this->completeUrl($item), [])->assertStatus(422)
            ->assertJsonValidationErrors('verification_method');
        $this->assertSame(0, Handover::query()->count());
    }

    public function test_authentication_secret_verification_is_rejected(): void
    {
        [$item, , , $actor] = $this->createApprovedFixture();

        $this->actingAs($actor, 'user')->postJson($this->completeUrl($item), [
            'verification_method' => 'student password confirmation',
            'verification_note' => 'OTP was requested',
        ])->assertStatus(422);
        $this->assertSame(0, Handover::query()->count());
        $this->assertSame(ItemStatus::IN_CUSTODY, $item->fresh()->status);
    }

    public function test_verification_values_are_encrypted_at_rest(): void
    {
        [$item, , , $actor] = $this->createApprovedFixture();

        $this->actingAs($actor, 'user')->postJson($this->completeUrl($item), $this->validPayload())->assertOk();
        $handover = Handover::query()->sole();
        $raw = DB::table('lost_found_handovers')->find($handover->id);

        $this->assertNotSame($this->validPayload()['verification_method'], $raw->verification_method);
        $this->assertNotSame($this->validPayload()['verification_note'], $raw->verification_note);
    }

    public function test_inactive_employee_is_rejected_at_application_domain_boundary(): void
    {
        [$item] = $this->createApprovedFixture();
        $inactive = $this->createEmployee(['lost_found.handover.complete'], false);

        $this->expectException(DomainException::class);

        try {
            app(EmployeeHandoverApplicationService::class)->completeHandover($inactive, $item, $this->validPayload());
        } finally {
            $this->assertSame(0, Handover::query()->count());
            $this->assertSame(ItemStatus::IN_CUSTODY, $item->fresh()->status);
        }
    }

    public function test_database_failure_rolls_back_complete_handover(): void
    {
        [$item, $claim, , $actor, $custodian] = $this->createApprovedFixture();
        DB::unprepared(sprintf(
            "CREATE TRIGGER handover_http_return_failure BEFORE UPDATE OF status ON lost_found_items WHEN NEW.id = %d AND NEW.status = 'returned' BEGIN SELECT RAISE(ABORT, 'forced handover failure'); END",
            $item->id,
        ));

        try {
            $this->actingAs($actor, 'user')->postJson($this->completeUrl($item), $this->validPayload());
        } finally {
            DB::unprepared('DROP TRIGGER IF EXISTS handover_http_return_failure');
        }

        $this->assertSame(0, Handover::query()->count());
        $this->assertSame(0, $item->custodyRecords()->where('event_type', CustodyEventType::HANDED_OVER->value)->count());
        $this->assertSame(ItemStatus::IN_CUSTODY, $item->fresh()->status);
        $this->assertSame($custodian->id, $item->current_custodian_user_id);
        $this->assertSame('Secure handover cabinet', $item->current_storage_location);
        $this->assertSame($claim->id, $item->approved_claim_id);
        $this->assertSame(ClaimStatus::APPROVED, $claim->fresh()->status);
        $this->assertSame(1, $item->custodyRecords()->count());
    }

    public function test_post_handover_claim_revocation_and_custody_operations_remain_blocked(): void
    {
        [$item, $claim, , $actor, $custodian] = $this->createApprovedFixture();
        $this->actingAs($actor, 'user')->postJson($this->completeUrl($item), $this->validPayload())->assertOk();

        $claims = app(ClaimResolutionService::class);
        $custody = app(CustodyService::class);

        foreach ([
            fn () => $claims->revoke($item->id, $claim->id, $custodian->id),
            fn () => $custody->receive($item->id, $custodian->id, $custodian->id, 'Reopened cabinet'),
            fn () => $custody->transfer($item->id, $custodian->id, $custodian->id, 'Secure handover cabinet', $actor->id, 'Reopened cabinet'),
            fn () => $custody->moveStorage($item->id, $custodian->id, $custodian->id, 'Secure handover cabinet', 'Reopened cabinet'),
        ] as $operation) {
            try {
                $operation();
                $this->fail('Expected terminal operation to be blocked.');
            } catch (DomainException) {
                $this->assertSame(ItemStatus::RETURNED, $item->fresh()->status);
            }
        }

        $this->assertSame($claim->id, $item->approved_claim_id);
        $this->assertSame(ClaimStatus::APPROVED, $claim->fresh()->status);
        $this->assertSame(1, Handover::query()->count());
        $this->assertSame(1, $item->custodyRecords()->where('event_type', CustodyEventType::HANDED_OVER->value)->count());
    }

    public function test_post_handover_image_mutation_remains_blocked_without_storage_writes(): void
    {
        [$item, , , $actor] = $this->createApprovedFixture([
            'lost_found.handover.complete',
            'lost_found.items.edit',
        ]);
        $this->actingAs($actor, 'user')->postJson($this->completeUrl($item), $this->validPayload())->assertOk();

        $response = $this->actingAs($actor, 'user')->postJson(
            route('admin.lost_found.items.images.store', $item->id),
            [
                'visibility' => FoundItemImageVisibility::PUBLIC_SAFE->value,
                'image' => UploadedFile::fake()->image('post-handover.jpg'),
            ],
        );

        $this->assertNotSame(201, $response->status());
        $this->assertSame(0, $item->images()->count());
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertSame([], Storage::disk('lost_found_private')->allFiles());
    }
}
