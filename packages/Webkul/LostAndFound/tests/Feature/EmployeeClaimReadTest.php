<?php

namespace Tests\Feature\LostAndFound;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;
use Webkul\LostAndFound\DataGrids\Employee\ClaimDataGrid;
use Webkul\LostAndFound\Enums\ClaimStatus;
use Webkul\LostAndFound\Enums\EvidenceType;
use Webkul\LostAndFound\Enums\ItemStatus;
use Webkul\LostAndFound\Enums\ReportStatus;
use Webkul\LostAndFound\Models\ClaimReview;
use Webkul\LostAndFound\Models\FoundItem;
use Webkul\LostAndFound\Models\LostFoundCategory;
use Webkul\LostAndFound\Models\LostFoundClaim;
use Webkul\LostAndFound\Models\LostReport;
use Webkul\LostAndFound\Services\CustodyService;
use Webkul\Student\Models\Student;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

class EmployeeClaimReadTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('lost_found_private');
    }

    private function employee(array|string $permissions = []): User
    {
        $role = Role::create([
            'name' => 'Claim read '.Str::random(8),
            'permission_type' => $permissions === 'all' ? 'all' : 'custom',
            'permissions' => $permissions === 'all' ? null : $permissions,
        ]);

        return User::create([
            'name' => 'Reviewer '.Str::random(8),
            'email' => 'EMPLOYEE-SECRET-'.Str::random(8).'@example.test',
            'password' => Hash::make('password'),
            'status' => true,
            'role_id' => $role->id,
        ]);
    }

    private function student(string $name = 'Claimant'): Student
    {
        return Student::create([
            'university_card_number' => 'STUDENT-SECRET-'.Str::random(8),
            'name' => $name,
            'password' => Hash::make('password'),
        ]);
    }

    private function item(User $actor): FoundItem
    {
        $category = LostFoundCategory::create(['code' => 'claims-'.Str::random(8)]);

        return FoundItem::create([
            'public_reference' => 'CLAIMS-'.Str::random(8),
            'category_id' => $category->id,
            'logged_by_user_id' => $actor->id,
            'status' => ItemStatus::REPORTED,
            'title' => 'Found item',
            'found_at' => now(),
            'reported_at' => now(),
        ]);
    }

    private function claim(FoundItem $item, ClaimStatus $status = ClaimStatus::SUBMITTED, string $name = 'Claimant'): LostFoundClaim
    {
        return LostFoundClaim::create([
            'found_item_id' => $item->id,
            'claimant_student_id' => $this->student($name)->id,
            'status' => $status,
            'submitted_at' => now(),
        ]);
    }

    private function grid(FoundItem $item, array $params = [])
    {
        return $this->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson(route('admin.lost_found.items.claims.index', ['id' => $item->id] + $params));
    }

    public function test_claims_view_alone_can_read_item_claim_list_and_detail(): void
    {
        $actor = $this->employee(['lost_found.claims.view']);
        $item = $this->item($actor);
        $claim = $this->claim($item);
        $this->actingAs($actor, 'user');

        $this->get(route('admin.lost_found.items.claims.index', $item->id))->assertOk()->assertSee($item->public_reference);
        $this->grid($item)->assertOk()->assertJsonPath('records.0.id', $claim->id)
            ->assertJsonPath('records.0.claimant_name', 'Claimant')
            ->assertJsonPath('records.0.is_approved_claim', 0);
        $this->get(route('admin.lost_found.claims.show', $claim->id))->assertOk()->assertSee('Claimant');
        $this->getJson(route('admin.lost_found.claims.show', $claim->id))->assertOk()
            ->assertJsonPath('data.found_item.id', $item->id)
            ->assertJsonPath('data.claimant.id', $claim->claimant_student_id);
    }

    public function test_guests_and_student_guard_redirect_to_admin_login(): void
    {
        $actor = $this->employee();
        $item = $this->item($actor);
        $claim = $this->claim($item);

        $this->get(route('admin.lost_found.items.claims.index', $item->id))->assertRedirect(route('admin.session.create'));
        $this->grid($item)->assertRedirect(route('admin.session.create'));
        $this->getJson(route('admin.lost_found.claims.show', $claim->id))->assertRedirect(route('admin.session.create'));
        $this->actingAs($this->student(), 'student');
        $this->grid($item)->assertRedirect(route('admin.session.create'));
    }

    public function test_other_permissions_do_not_grant_claim_reads(): void
    {
        $admin = $this->employee('all');
        $item = $this->item($admin);
        $claim = $this->claim($item);

        foreach (['lost_found.items.view', 'lost_found.claims.review', 'lost_found.claims.approve', 'lost_found.claims.reject', 'lost_found.custody.manage'] as $permission) {
            $this->actingAs($this->employee([$permission]), 'user');
            $this->grid($item)->assertStatus(401);
            $this->getJson(route('admin.lost_found.claims.show', $claim->id))->assertStatus(401);
        }
    }

    public function test_direct_grid_invocation_requires_claim_read_permission(): void
    {
        $this->actingAs($this->employee(['lost_found.items.view']), 'user');
        $this->expectException(AuthorizationException::class);
        app(ClaimDataGrid::class)->process();
    }

    public function test_missing_item_and_claim_are_not_found(): void
    {
        $this->actingAs($this->employee(['lost_found.claims.view']), 'user');
        $this->getJson(route('admin.lost_found.items.claims.index', 987654321))->assertNotFound();
        $this->getJson(route('admin.lost_found.claims.show', 987654321))->assertNotFound();
    }

    public function test_claims_are_isolated_and_all_statuses_are_visible(): void
    {
        $actor = $this->employee(['lost_found.claims.view']);
        $first = $this->item($actor);
        $second = $this->item($actor);
        $claims = [];
        foreach (ClaimStatus::cases() as $status) {
            $claims[] = $this->claim($first, $status, 'First claimant '.count($claims));
        }
        $other = $this->claim($second, ClaimStatus::SUBMITTED, 'OTHER-CLAIMANT');
        $this->actingAs($actor, 'user');

        $list = $this->grid($first)->assertOk()->assertJsonPath('meta.total', 6);
        $this->assertStringNotContainsString('OTHER-CLAIMANT', $list->getContent());
        $this->assertStringNotContainsString((string) $other->id.'<', $list->getContent());
        foreach (ClaimStatus::cases() as $status) {
            $this->grid($first, ['filters' => ['status' => [$status->value]]])
                ->assertOk()->assertJsonPath('meta.total', 1);
        }
        $this->getJson(route('admin.lost_found.claims.show', $claims[0]->id))->assertOk()
            ->assertDontSee('OTHER-CLAIMANT');
    }

    public function test_evidence_and_review_history_are_detail_only_and_private_metadata_is_excluded(): void
    {
        $actor = $this->employee(['lost_found.claims.view']);
        $item = $this->item($actor);
        $first = $this->claim($item, ClaimStatus::SUBMITTED, 'FIRST-CLAIMANT');
        $other = $this->claim($item, ClaimStatus::SUBMITTED, 'SECOND-CLAIMANT');
        $item->privateDetail()->create(['identifying_details' => 'PRIVATE-ITEM-DETAIL', 'staff_notes' => 'PRIVATE-ITEM-NOTE']);
        LostReport::create([
            'public_reference' => 'REPORT-'.Str::random(8),
            'student_id' => $first->claimant_student_id,
            'status' => ReportStatus::ACTIVE,
            'title' => 'Other lost report',
            'private_description' => 'PRIVATE-REPORT-DESCRIPTION',
            'submitted_at' => now(),
        ]);
        app(CustodyService::class)->receive($item->id, $actor->id, $actor->id, 'PRIVATE-CUSTODY-LOCATION', null, 'PRIVATE-CUSTODY-NOTE');
        $first->evidence()->create(['evidence_type' => EvidenceType::TEXT_DESCRIPTION, 'text_value' => '<script>alert(1)</script> FIRST-EVIDENCE', 'submitted_at' => now()]);
        $first->evidence()->create(['evidence_type' => EvidenceType::IMAGE_ATTACHMENT, 'file_path' => 'PRIVATE-STORAGE-KEY', 'storage_key_hash' => str_repeat('a', 64), 'original_name' => 'PRIVATE-FILENAME', 'mime_type' => 'image/png', 'byte_size' => 10, 'submitted_at' => now()]);
        $other->evidence()->create(['evidence_type' => EvidenceType::TEXT_DESCRIPTION, 'text_value' => 'SECOND-EVIDENCE', 'submitted_at' => now()]);
        ClaimReview::create(['claim_id' => $first->id, 'reviewer_user_id' => $actor->id, 'from_status' => ClaimStatus::SUBMITTED, 'to_status' => ClaimStatus::UNDER_REVIEW, 'staff_notes' => '<script>alert(2)</script> FIRST-NOTE', 'reviewed_at' => now()]);
        ClaimReview::create(['claim_id' => $other->id, 'reviewer_user_id' => $actor->id, 'from_status' => ClaimStatus::SUBMITTED, 'to_status' => ClaimStatus::UNDER_REVIEW, 'staff_notes' => 'SECOND-NOTE', 'reviewed_at' => now()]);
        $this->actingAs($actor, 'user');

        $list = $this->grid($item)->assertOk()->assertJsonPath('records.0.image_evidence_count', 0);
        foreach (['FIRST-EVIDENCE', 'FIRST-NOTE', 'PRIVATE-STORAGE-KEY', 'PRIVATE-FILENAME', 'SECOND-EVIDENCE'] as $marker) {
            $this->assertStringNotContainsString($marker, $list->getContent());
        }

        $detail = $this->getJson(route('admin.lost_found.claims.show', $first->id))->assertOk()
            ->assertJsonPath('data.evidence.0.text', '<script>alert(1)</script> FIRST-EVIDENCE')
            ->assertJsonPath('data.evidence.1.type', EvidenceType::IMAGE_ATTACHMENT->value)
            ->assertJsonPath('data.evidence.1.text', null)
            ->assertJsonPath('data.reviews.0.staff_notes', '<script>alert(2)</script> FIRST-NOTE');
        foreach (['PRIVATE-STORAGE-KEY', 'PRIVATE-FILENAME', str_repeat('a', 64), 'SECOND-EVIDENCE', 'SECOND-NOTE', 'SECOND-CLAIMANT', 'EMPLOYEE-SECRET', 'STUDENT-SECRET', 'PRIVATE-ITEM-DETAIL', 'PRIVATE-ITEM-NOTE', 'PRIVATE-REPORT-DESCRIPTION', 'PRIVATE-CUSTODY-LOCATION', 'PRIVATE-CUSTODY-NOTE'] as $marker) {
            $this->assertStringNotContainsString($marker, $detail->getContent());
        }
        $html = $this->get(route('admin.lost_found.claims.show', $first->id))->assertOk();
        $html->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertSee('&lt;script&gt;alert(2)&lt;/script&gt;', false);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html->getContent());
    }

    public function test_winner_is_derived_only_from_item_pointer(): void
    {
        $actor = $this->employee(['lost_found.claims.view']);
        $item = $this->item($actor);
        $first = $this->claim($item);
        $second = $this->claim($item);
        $this->actingAs($actor, 'user');
        $this->grid($item)->assertOk()->assertJsonPath('records.0.is_approved_claim', 0)->assertJsonPath('records.1.is_approved_claim', 0);
        DB::table('lost_found_items')->where('id', $item->id)->update(['approved_claim_id' => $first->id]);
        $records = $this->grid($item)->assertOk()->json('records');
        $this->assertSame(1, collect($records)->sum('is_approved_claim'));
        $this->assertTrue((bool) collect($records)->firstWhere('id', $first->id)['is_approved_claim']);
        $this->getJson(route('admin.lost_found.claims.show', $second->id))->assertJsonPath('data.is_approved_claim', false);
    }

    public function test_grid_validates_filter_sort_and_bounded_pagination(): void
    {
        $actor = $this->employee(['lost_found.claims.view']);
        $item = $this->item($actor);
        $this->actingAs($actor, 'user');
        $this->grid($item)->assertOk()->assertJsonPath('meta.total', 0);
        for ($i = 0; $i < 12; $i++) {
            $this->claim($item);
        }
        $this->grid($item, ['pagination' => ['page' => 2, 'per_page' => 10]])->assertJsonPath('meta.total', 12)->assertJsonCount(2, 'records');
        $this->grid($item, ['sort' => ['column' => 'id', 'order' => 'asc']])->assertOk();
        foreach ([['filters' => ['status' => ['invalid']]], ['filters' => ['all' => ["' OR 1=1 --"]]], ['sort' => ['column' => 'password', 'order' => 'asc']], ['pagination' => ['per_page' => 100000]], ['export' => 1]] as $params) {
            $this->grid($item, $params)->assertStatus(422);
        }
    }

    public function test_claim_list_query_count_is_constant_and_reads_do_not_write(): void
    {
        $actor = $this->employee(['lost_found.claims.view']);
        $item = $this->item($actor);
        for ($i = 0; $i < 25; $i++) {
            $this->claim($item);
        }
        $this->actingAs($actor, 'user');
        $before = [DB::table('lost_found_claims')->count(), DB::table('lost_found_claim_reviews')->count(), DB::table('lost_found_claim_evidence')->count(), $item->fresh()->updated_at?->toISOString()];
        $queries = [];
        DB::listen(static function ($event) use (&$queries): void {
            $queries[] = $event->sql;
        });
        $this->grid($item, ['pagination' => ['per_page' => 20]])->assertOk()->assertJsonCount(20, 'records');
        $selects = array_values(array_filter($queries, static fn ($sql): bool => str_starts_with(strtolower(ltrim($sql)), 'select')));
        // The shared Admin locale middleware adds one core-config read.
        $this->assertCount(1, array_filter($selects, static fn ($sql): bool => str_contains($sql, 'core_config')));
        $this->assertCount(4, array_filter($selects, static fn ($sql): bool => ! str_contains($sql, 'core_config')));
        $this->assertSame($before, [DB::table('lost_found_claims')->count(), DB::table('lost_found_claim_reviews')->count(), DB::table('lost_found_claim_evidence')->count(), $item->fresh()->updated_at?->toISOString()]);
        Storage::disk('lost_found_private')->assertDirectoryEmpty('/');
    }

    public function test_claim_detail_query_count_is_bounded_for_multiple_evidence_and_reviews(): void
    {
        $actor = $this->employee(['lost_found.claims.view']);
        $item = $this->item($actor);
        $claim = $this->claim($item);
        for ($i = 0; $i < 12; $i++) {
            $claim->evidence()->create(['evidence_type' => EvidenceType::TEXT_DESCRIPTION, 'text_value' => 'Detail '.$i, 'submitted_at' => now()]);
            ClaimReview::create(['claim_id' => $claim->id, 'reviewer_user_id' => $actor->id, 'from_status' => ClaimStatus::SUBMITTED, 'to_status' => ClaimStatus::UNDER_REVIEW, 'staff_notes' => 'Note '.$i, 'reviewed_at' => now()]);
        }
        $this->actingAs($actor, 'user');
        $queries = [];
        DB::listen(static function ($event) use (&$queries): void {
            $queries[] = $event->sql;
        });
        $this->getJson(route('admin.lost_found.claims.show', $claim->id))->assertOk()
            ->assertJsonCount(12, 'data.evidence')->assertJsonCount(12, 'data.reviews');
        $selects = array_values(array_filter($queries, static fn ($sql): bool => str_starts_with(strtolower(ltrim($sql)), 'select')));
        $this->assertCount(1, array_filter($selects, static fn ($sql): bool => str_contains($sql, 'core_config')));
        $this->assertCount(5, array_filter($selects, static fn ($sql): bool => ! str_contains($sql, 'core_config')));
    }

    public function test_user_controlled_claimant_name_is_escaped_in_html_grid(): void
    {
        $actor = $this->employee(['lost_found.claims.view']);
        $item = $this->item($actor);
        $this->claim($item, ClaimStatus::SUBMITTED, '<script>alert(3)</script>');
        $this->actingAs($actor, 'user');
        $record = $this->grid($item)->assertOk()->json('records.0');
        $this->assertSame('&lt;script&gt;alert(3)&lt;/script&gt;', $record['claimant_name']);
        $this->assertStringNotContainsString('<script>', json_encode($record));
    }
}
