<?php

namespace Tests\Feature\LostAndFound;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;
use Webkul\LostAndFound\DataGrids\Employee\FoundItemDataGrid;
use Webkul\LostAndFound\Enums\ClaimStatus;
use Webkul\LostAndFound\Enums\EvidenceType;
use Webkul\LostAndFound\Enums\ItemStatus;
use Webkul\LostAndFound\Enums\ReportStatus;
use Webkul\LostAndFound\Models\FoundItem;
use Webkul\LostAndFound\Models\LostFoundCategory;
use Webkul\LostAndFound\Models\LostFoundClaim;
use Webkul\LostAndFound\Models\LostReport;
use Webkul\LostAndFound\Services\ClaimResolutionService;
use Webkul\LostAndFound\Services\CustodyService;
use Webkul\LostAndFound\Services\HandoverService;
use Webkul\Student\Models\Student;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

class EmployeeFoundItemReadTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('lost_found_private');
    }

    private function employee(array|string $permissions = []): User
    {
        $role = Role::create([
            'name' => 'Read role '.Str::random(8),
            'permission_type' => $permissions === 'all' ? 'all' : 'custom',
            'permissions' => $permissions === 'all' ? null : $permissions,
        ]);

        return User::create([
            'name' => 'Read staff '.Str::random(8),
            'email' => Str::random(12).'@example.test',
            'password' => Hash::make('password'),
            'status' => true,
            'role_id' => $role->id,
        ]);
    }

    private function student(): Student
    {
        return Student::create([
            'university_card_number' => 'READ-'.Str::random(12),
            'name' => 'Private claimant '.Str::random(8),
            'password' => Hash::make('password'),
        ]);
    }

    private function item(User $logger, string $reference = 'READ-ITEM', string $title = 'Found keys', ?LostFoundCategory $category = null): FoundItem
    {
        $category ??= LostFoundCategory::create(['code' => 'read-'.Str::random(8)]);

        return FoundItem::create([
            'public_reference' => $reference.'-'.Str::random(8),
            'category_id' => $category->id,
            'logged_by_user_id' => $logger->id,
            'status' => ItemStatus::REPORTED,
            'title' => $title,
            'found_location' => 'Library',
            'found_at' => now(),
            'reported_at' => now(),
        ]);
    }

    private function grid(array $params = [])
    {
        return $this->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson(route('admin.lost_found.items.index', $params));
    }

    public function test_authorized_html_and_grid_use_one_read_route(): void
    {
        $actor = $this->employee(['lost_found.items.view']);
        $item = $this->item($actor);

        $this->actingAs($actor, 'user')->get(route('admin.lost_found.items.index'))
            ->assertOk()
            ->assertSee('lost-found/items', false);

        $response = $this->grid()->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('records.0.id', $item->id)
            ->assertJsonPath('records.0.public_reference', $item->public_reference);

        $this->assertSame([], $response->json('actions'));
        $this->assertSame([], $response->json('mass_actions'));
    }

    public function test_unauthenticated_html_and_ajax_requests_redirect_to_admin_login(): void
    {
        $this->get(route('admin.lost_found.items.index'))->assertStatus(302);
        $this->grid()->assertStatus(302);
    }

    public function test_student_guard_cannot_read_employee_items(): void
    {
        $this->actingAs($this->student(), 'student');

        $this->grid()->assertStatus(302);
    }

    public function test_all_other_single_permissions_are_denied(): void
    {
        foreach ([
            'lost_found.claims.view',
            'lost_found.custody.manage',
            'lost_found.handover.complete',
            'lost_found.items.edit',
        ] as $permission) {
            $this->actingAs($this->employee([$permission]), 'user');
            $this->grid()->assertStatus(401);
        }
    }

    public function test_read_only_permission_cannot_mutate_item_claim_custody_or_handover(): void
    {
        $actor = $this->employee(['lost_found.items.view']);
        $item = $this->item($actor);
        $this->actingAs($actor, 'user');

        $this->putJson(route('admin.lost_found.items.update', $item->id), ['title' => 'Changed'])->assertStatus(401);
        $this->postJson(route('admin.lost_found.custody.receive', $item->id), ['storage_location' => 'Vault'])->assertStatus(401);
        $this->postJson(route('admin.lost_found.handover.complete', $item->id), ['verification_method' => 'Card'])->assertStatus(401);

        $this->assertSame('Found keys', $item->fresh()->title);
    }

    public function test_direct_grid_call_requires_item_view_permission(): void
    {
        $this->actingAs($this->employee(['lost_found.claims.view']), 'user');
        $this->expectException(AuthorizationException::class);

        app(FoundItemDataGrid::class)->process();
    }

    public function test_item_only_view_excludes_claim_and_custody_read_fields(): void
    {
        $admin = $this->employee('all');
        $viewer = $this->employee(['lost_found.items.view']);
        $item = $this->item($admin);
        app(CustodyService::class)->receive($item->id, $admin->id, $admin->id, 'Hidden vault');
        LostFoundClaim::create([
            'found_item_id' => $item->id,
            'claimant_student_id' => $this->student()->id,
            'status' => ClaimStatus::SUBMITTED,
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($viewer, 'user')->grid()->assertOk();
        $record = $response->json('records.0');

        $this->assertArrayNotHasKey('claim_count', $record);
        $this->assertArrayNotHasKey('has_approved_claim', $record);
        $this->assertArrayNotHasKey('current_custodian_name', $record);
        $this->assertStringNotContainsString('Hidden vault', $response->getContent());
        $this->assertStringNotContainsString($admin->name, $response->getContent());
    }

    public function test_claim_and_custody_fields_require_their_respective_permissions(): void
    {
        $admin = $this->employee('all');
        $item = $this->item($admin);
        app(CustodyService::class)->receive($item->id, $admin->id, $admin->id, 'Vault');
        LostFoundClaim::create([
            'found_item_id' => $item->id,
            'claimant_student_id' => $this->student()->id,
            'status' => ClaimStatus::SUBMITTED,
            'submitted_at' => now(),
        ]);

        $claimsViewer = $this->employee(['lost_found.items.view', 'lost_found.claims.view']);
        $claimsRecord = $this->actingAs($claimsViewer, 'user')->grid()->assertOk()->json('records.0');
        $this->assertSame(1, $claimsRecord['claim_count']);
        $this->assertArrayNotHasKey('current_custodian_name', $claimsRecord);

        $custodyViewer = $this->employee(['lost_found.items.view', 'lost_found.custody.manage']);
        $custodyRecord = $this->actingAs($custodyViewer, 'user')->grid()->assertOk()->json('records.0');
        $this->assertSame($admin->name, $custodyRecord['current_custodian_name']);
        $this->assertArrayNotHasKey('claim_count', $custodyRecord);
    }

    public function test_case_insensitive_public_reference_search_and_literal_wildcards(): void
    {
        $actor = $this->employee(['lost_found.items.view']);
        $item = $this->item($actor, 'READ-MATCH');
        $this->item($actor, 'READ-OTHER');
        $this->actingAs($actor, 'user');

        $this->grid(['filters' => ['all' => [strtolower($item->public_reference)]]])
            ->assertOk()->assertJsonPath('meta.total', 1);
        $this->grid(['filters' => ['all' => ['%']]])
            ->assertOk()->assertJsonPath('meta.total', 0);
        $this->grid(['filters' => ['all' => ["' OR 1=1 --"]]])
            ->assertOk()->assertJsonPath('meta.total', 0);
    }

    public function test_status_filter_and_allowlisted_sorting(): void
    {
        $actor = $this->employee(['lost_found.items.view']);
        $first = $this->item($actor, 'READ-A', 'A title');
        $second = $this->item($actor, 'READ-B', 'Z title');
        $this->actingAs($actor, 'user');

        $this->grid(['filters' => ['status' => [ItemStatus::REPORTED->value]]])
            ->assertOk()->assertJsonPath('meta.total', 2);
        $this->grid(['sort' => ['column' => 'title', 'order' => 'asc']])
            ->assertOk()->assertJsonPath('records.0.id', $first->id)
            ->assertJsonPath('records.1.id', $second->id);
        $this->grid(['filters' => ['status' => ['invalid']]])->assertStatus(422);
        $this->grid(['filters' => ['password' => ['x']]])->assertStatus(422);
        $this->grid(['sort' => ['column' => 'password', 'order' => 'asc']])->assertStatus(422);
    }

    public function test_pagination_is_bounded_and_empty_results_are_normal(): void
    {
        $actor = $this->employee(['lost_found.items.view']);
        $this->actingAs($actor, 'user');
        $this->grid()->assertOk()->assertJsonPath('meta.total', 0)->assertJsonCount(0, 'records');

        for ($index = 0; $index < 12; $index++) {
            $this->item($actor);
        }

        $this->grid(['pagination' => ['page' => 2, 'per_page' => 10]])
            ->assertOk()->assertJsonPath('meta.total', 12)->assertJsonCount(2, 'records');
        $this->grid(['pagination' => ['page' => 1, 'per_page' => 100000]])->assertStatus(422);
        $this->grid(['export' => 1])->assertStatus(422);
    }

    public function test_sensitive_data_and_storage_keys_do_not_enter_grid(): void
    {
        $admin = $this->employee('all');
        $item = $this->item($admin);
        $student = $this->student();
        $item->privateDetail()->create([
            'identifying_details' => 'UNIQUE-IDENTIFYING-SECRET',
            'serial_fragment' => 'UNIQUE-SERIAL-SECRET',
            'staff_notes' => 'UNIQUE-STAFF-SECRET',
        ]);
        $claim = LostFoundClaim::create([
            'found_item_id' => $item->id,
            'claimant_student_id' => $student->id,
            'status' => ClaimStatus::SUBMITTED,
            'submitted_at' => now(),
        ]);
        $claim->evidence()->create([
            'evidence_type' => EvidenceType::TEXT_DESCRIPTION,
            'text_value' => 'UNIQUE-EVIDENCE-SECRET',
            'submitted_at' => now(),
        ]);
        LostReport::create([
            'public_reference' => 'READ-REPORT-'.Str::random(8),
            'student_id' => $student->id,
            'category_id' => $item->category_id,
            'status' => ReportStatus::ACTIVE,
            'title' => 'Related lost report',
            'private_description' => 'UNIQUE-REPORT-SECRET',
        ]);
        app(CustodyService::class)->receive($item->id, $admin->id, $admin->id, 'UNIQUE-STORAGE-SECRET', now()->subMinute(), 'UNIQUE-CUSTODY-NOTE');
        $claim = app(ClaimResolutionService::class)->review($item->id, $claim->id, $admin->id, ClaimStatus::UNDER_REVIEW);
        $claim = app(ClaimResolutionService::class)->approve($item->id, $claim->id, $admin->id);
        app(HandoverService::class)->complete($item->id, $claim->id, $student->id, $admin->id, 'UNIQUE-VERIFICATION-SECRET', 'UNIQUE-VERIFICATION-NOTE');

        $response = $this->actingAs($admin, 'user')->grid()->assertOk();
        $body = $response->getContent();

        foreach (['UNIQUE-IDENTIFYING-SECRET', 'UNIQUE-SERIAL-SECRET', 'UNIQUE-STAFF-SECRET', 'UNIQUE-EVIDENCE-SECRET', 'UNIQUE-REPORT-SECRET', 'UNIQUE-STORAGE-SECRET', 'UNIQUE-CUSTODY-NOTE', 'UNIQUE-VERIFICATION-SECRET', 'UNIQUE-VERIFICATION-NOTE', 'storage_key', 'public_reference_key', 'approved_claim_id', 'password', 'recipient_student_id'] as $secret) {
            $this->assertStringNotContainsString($secret, $body);
        }
        $this->assertSame(1, $response->json('records.0.claim_count'));
        $this->assertSame(1, $response->json('records.0.has_approved_claim'));
    }

    public function test_html_payload_is_escaped_for_the_grid_html_renderer(): void
    {
        $actor = $this->employee(['lost_found.items.view']);
        $this->item($actor, 'READ-XSS', '<img src=x onerror=alert(1)>');

        $record = $this->actingAs($actor, 'user')->grid()->assertOk()->json('records.0');

        $this->assertSame('&lt;img src=x onerror=alert(1)&gt;', $record['title']);
    }

    public function test_read_requests_have_no_domain_or_storage_side_effects(): void
    {
        $actor = $this->employee(['lost_found.items.view']);
        $item = $this->item($actor);
        $before = $item->fresh()->getRawOriginal('updated_at');

        $this->actingAs($actor, 'user')->grid(['filters' => ['all' => ['keys']]])->assertOk();

        $this->assertSame($before, $item->fresh()->getRawOriginal('updated_at'));
        $this->assertSame(0, DB::table('lost_found_claims')->count());
        $this->assertSame(0, DB::table('lost_found_custody_records')->count());
        $this->assertSame(0, DB::table('lost_found_handovers')->count());
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertSame([], Storage::disk('lost_found_private')->allFiles());
    }

    public function test_list_query_count_is_bounded_for_many_rows(): void
    {
        $actor = $this->employee(['lost_found.items.view', 'lost_found.claims.view', 'lost_found.custody.manage']);
        for ($index = 0; $index < 25; $index++) {
            $this->item($actor);
        }

        $selects = 0;
        DB::listen(function ($query) use (&$selects): void {
            if (str_starts_with(strtolower($query->sql), 'select') && str_contains($query->sql, 'lost_found_items')) {
                $selects++;
            }
        });

        $this->actingAs($actor, 'user')->grid(['pagination' => ['page' => 1, 'per_page' => 20]])
            ->assertOk()->assertJsonCount(20, 'records');

        $this->assertSame(2, $selects);
    }
}
