<?php

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Webkul\LostAndFound\Enums\ClaimStatus;
use Webkul\LostAndFound\Enums\EvidenceType;
use Webkul\LostAndFound\Enums\ItemStatus;
use Webkul\LostAndFound\Models\ClaimReview;
use Webkul\LostAndFound\Models\FoundItem;
use Webkul\LostAndFound\Models\LostFoundCategory;
use Webkul\LostAndFound\Models\LostFoundClaim;
use Webkul\LostAndFound\Repositories\LostFoundClaimRepository;
use Webkul\LostAndFound\Services\ClaimEvidenceService;
use Webkul\LostAndFound\Services\ClaimResolutionService;
use Webkul\Student\Models\Student;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

uses(DatabaseTransactions::class);

function makeClaimPersistenceActors(int $studentCount = 1): array
{
    $role = Role::create([
        'name' => 'Claim Reviewer '.Str::random(8),
        'description' => null,
        'permission_type' => 'all',
        'permissions' => null,
    ]);
    $user = User::create([
        'name' => 'Claim Reviewer',
        'email' => Str::random(12).'@example.test',
        'password' => Hash::make(Str::random(32)),
        'status' => true,
        'role_id' => $role->id,
    ]);
    $students = collect(range(1, $studentCount))->map(fn (): Student => Student::create([
        'university_card_number' => 'CLAIM-'.Str::random(12),
        'password' => Str::random(32),
        'name' => 'Claimant Student',
    ]));

    return [$user, ...$students];
}

function makeClaimableItem(User $user): FoundItem
{
    $category = LostFoundCategory::create(['code' => 'claim-'.Str::random(10)]);

    return FoundItem::create([
        'public_reference' => 'CLAIM-ITEM-'.Str::random(12),
        'category_id' => $category->id,
        'logged_by_user_id' => $user->id,
        'status' => ItemStatus::REPORTED,
        'title' => 'Claimable item',
        'found_at' => now(),
        'reported_at' => now(),
    ]);
}

function makeClaim(FoundItem $item, Student $student): LostFoundClaim
{
    return app(LostFoundClaimRepository::class)->create([
        'found_item_id' => $item->id,
        'claimant_student_id' => $student->id,
    ]);
}

function putClaimUnderReview(FoundItem $item, LostFoundClaim $claim, User $reviewer): LostFoundClaim
{
    return app(ClaimResolutionService::class)->review(
        $item->id,
        $claim->id,
        $reviewer->id,
        ClaimStatus::UNDER_REVIEW,
        'We are reviewing your claim.',
        'Initial review.',
    );
}

function assertApprovalInvariant(FoundItem $item, LostFoundClaim $approved, array $competing = []): void
{
    expect($item->refresh()->approved_claim_id)->toBe($approved->id)
        ->and($approved->refresh()->found_item_id)->toBe($item->id)
        ->and($approved->status)->toBe(ClaimStatus::APPROVED);

    foreach ($competing as $claim) {
        expect($claim->refresh()->status)->not->toBe(ClaimStatus::APPROVED);
    }
}

test('claim wave schema has the required columns and excludes later-wave tables', function () {
    expect(Schema::getColumnListing('lost_found_claims'))->toBe([
        'id', 'found_item_id', 'claimant_student_id', 'status', 'submitted_at', 'withdrawn_at',
        'created_at', 'updated_at',
    ])->and(Schema::getColumnListing('lost_found_claim_evidence'))->toBe([
        'id', 'claim_id', 'evidence_type', 'text_value', 'file_path', 'original_name',
        'mime_type', 'byte_size', 'submitted_at', 'created_at', 'updated_at', 'storage_key_hash',
    ])->and(Schema::getColumnListing('lost_found_claim_reviews'))->toBe([
        'id', 'claim_id', 'reviewer_user_id', 'from_status', 'to_status', 'claimant_message',
        'staff_notes', 'reviewed_at', 'created_at', 'updated_at',
    ])->and(Schema::hasColumn('lost_found_items', 'approved_claim_id'))->toBeTrue();

    foreach ([
        'lost_found_locations',
        'lost_found_item_attachments', 'lost_found_report_attachments',
        'lost_found_dispositions', 'potential_matches',
    ] as $table) {
        expect(Schema::hasTable($table))->toBeFalse();
    }
});

test('claim aggregate relationships and enum casts resolve correctly', function () {
    [$reviewer, $student] = makeClaimPersistenceActors();
    $item = makeClaimableItem($reviewer);
    $claim = makeClaim($item, $student);
    $evidence = app(ClaimEvidenceService::class)->addTextEvidence(
        $claim,
        EvidenceType::MARKING_DETAIL,
        'A small blue ownership mark.',
    );
    $claim = putClaimUnderReview($item, $claim, $reviewer);
    $review = $claim->reviews()->latest('id')->firstOrFail();

    expect($claim->status)->toBe(ClaimStatus::UNDER_REVIEW)
        ->and($evidence->evidence_type)->toBe(EvidenceType::MARKING_DETAIL)
        ->and($claim->foundItem->is($item))->toBeTrue()
        ->and($claim->claimant->is($student))->toBeTrue()
        ->and($claim->evidence->first()->is($evidence))->toBeTrue()
        ->and($claim->reviews->first()->is($review))->toBeTrue()
        ->and($item->claims->first()->is($claim))->toBeTrue()
        ->and($evidence->claim->is($claim))->toBeTrue()
        ->and($review->claim->is($claim))->toBeTrue()
        ->and($review->reviewer->is($reviewer))->toBeTrue();
});

test('one student cannot create a second lifecycle for the same item', function () {
    [$reviewer, $student] = makeClaimPersistenceActors();
    $item = makeClaimableItem($reviewer);
    makeClaim($item, $student);

    expect(fn () => makeClaim($item, $student))->toThrow(QueryException::class);
});

test('evidence and review private values are encrypted and hidden', function () {
    [$reviewer, $student] = makeClaimPersistenceActors();
    $item = makeClaimableItem($reviewer);
    $claim = makeClaim($item, $student);
    $evidence = app(ClaimEvidenceService::class)->addTextEvidence(
        $claim,
        EvidenceType::TEXT_DESCRIPTION,
        'A private ownership description.',
    );
    $fileEvidence = $claim->evidence()->create([
        'evidence_type' => EvidenceType::IMAGE_ATTACHMENT,
        'file_path' => 'private/lost-found/evidence/example.bin',
        'storage_key_hash' => hash('sha256', 'private/lost-found/evidence/example.bin'),
        'original_name' => 'ownership-photo.jpg',
        'mime_type' => 'image/jpeg',
        'byte_size' => 2048,
        'submitted_at' => now(),
    ]);
    $claim = putClaimUnderReview($item, $claim, $reviewer);
    $review = $claim->reviews()->latest('id')->firstOrFail();
    $rawEvidence = DB::table('lost_found_claim_evidence')->find($evidence->id);
    $rawFileEvidence = DB::table('lost_found_claim_evidence')->find($fileEvidence->id);
    $rawReview = DB::table('lost_found_claim_reviews')->find($review->id);

    expect($rawEvidence->text_value)->not->toBe('A private ownership description.')
        ->and($rawFileEvidence->file_path)->not->toBe('private/lost-found/evidence/example.bin')
        ->and($rawFileEvidence->original_name)->not->toBe('ownership-photo.jpg')
        ->and($rawReview->claimant_message)->not->toBe('We are reviewing your claim.')
        ->and($rawReview->staff_notes)->not->toBe('Initial review.')
        ->and($evidence->text_value)->toBe('A private ownership description.')
        ->and($review->staff_notes)->toBe('Initial review.')
        ->and($evidence->toArray())->not->toHaveKeys(['text_value', 'file_path', 'storage_key_hash', 'original_name', 'mime_type', 'byte_size'])
        ->and($fileEvidence->toArray())->not->toHaveKeys(['text_value', 'file_path', 'storage_key_hash', 'original_name', 'mime_type', 'byte_size'])
        ->and($review->toArray())->not->toHaveKeys(['claimant_message', 'staff_notes'])
        ->and($item->toArray())->not->toHaveKey('approved_claim_id');
});

test('supported evidence workflow rejects authentication secrets', function () {
    [$reviewer, $student] = makeClaimPersistenceActors();
    $claim = makeClaim(makeClaimableItem($reviewer), $student);
    $service = app(ClaimEvidenceService::class);

    foreach (['password', 'PIN', 'device unlock code', 'unlock pattern', 'OTP', 'recovery code', 'authentication token', 'security answer'] as $secret) {
        expect(fn () => $service->addTextEvidence(
            $claim,
            EvidenceType::TEXT_DESCRIPTION,
            "My {$secret} is forbidden",
        ))->toThrow(InvalidArgumentException::class);
    }

    expect($claim->evidence()->count())->toBe(0);
});

test('claim review history is append-only through the model API', function () {
    [$reviewer, $student] = makeClaimPersistenceActors();
    $item = makeClaimableItem($reviewer);
    $claim = putClaimUnderReview($item, makeClaim($item, $student), $reviewer);
    $review = $claim->reviews()->firstOrFail();
    $review->staff_notes = 'Attempted rewrite';

    expect(fn () => $review->save())->toThrow(LogicException::class);

    $freshReview = ClaimReview::findOrFail($review->id);
    expect(fn () => $freshReview->delete())->toThrow(LogicException::class);
});

test('approval establishes one authoritative winner and rejects competing claims', function () {
    [$reviewer, $studentA, $studentB] = makeClaimPersistenceActors(2);
    $item = makeClaimableItem($reviewer);
    $claimA = putClaimUnderReview($item, makeClaim($item, $studentA), $reviewer);
    $claimB = putClaimUnderReview($item, makeClaim($item, $studentB), $reviewer);

    $approved = app(ClaimResolutionService::class)->approve(
        $item->id,
        $claimA->id,
        $reviewer->id,
        'Your ownership claim was approved.',
        'Evidence confirmed.',
    );

    assertApprovalInvariant($item, $approved, [$claimB]);
    expect($claimB->refresh()->status)->toBe(ClaimStatus::REJECTED)
        ->and($claimB->reviews()->where('to_status', ClaimStatus::REJECTED->value)->count())->toBe(1)
        ->and($item->refresh()->status)->toBe(ItemStatus::REPORTED);

    expect(fn () => app(ClaimResolutionService::class)->approve(
        $item->id,
        $claimB->id,
        $reviewer->id,
    ))->toThrow(DomainException::class);

    assertApprovalInvariant($item, $approved, [$claimB]);
});

test('reverse approval ordering still produces exactly one winner', function () {
    [$reviewer, $studentA, $studentB] = makeClaimPersistenceActors(2);
    $item = makeClaimableItem($reviewer);
    $claimA = putClaimUnderReview($item, makeClaim($item, $studentA), $reviewer);
    $claimB = putClaimUnderReview($item, makeClaim($item, $studentB), $reviewer);
    $approved = app(ClaimResolutionService::class)->approve($item->id, $claimB->id, $reviewer->id);

    expect(fn () => app(ClaimResolutionService::class)->approve(
        $item->id,
        $claimA->id,
        $reviewer->id,
    ))->toThrow(DomainException::class);

    assertApprovalInvariant($item, $approved, [$claimA]);
});

test('a claim from another item cannot be approved against the locked item', function () {
    [$reviewer, $student] = makeClaimPersistenceActors();
    $itemA = makeClaimableItem($reviewer);
    $itemB = makeClaimableItem($reviewer);
    $claimB = putClaimUnderReview($itemB, makeClaim($itemB, $student), $reviewer);

    expect(fn () => app(ClaimResolutionService::class)->approve(
        $itemA->id,
        $claimB->id,
        $reviewer->id,
    ))->toThrow(DomainException::class);

    expect($itemA->refresh()->approved_claim_id)->toBeNull()
        ->and($itemB->refresh()->approved_claim_id)->toBeNull()
        ->and($claimB->refresh()->status)->toBe(ClaimStatus::UNDER_REVIEW);
});

test('terminal claims cannot be approved', function () {
    [$reviewer, $studentA, $studentB] = makeClaimPersistenceActors(2);
    $itemA = makeClaimableItem($reviewer);
    $rejected = putClaimUnderReview($itemA, makeClaim($itemA, $studentA), $reviewer);
    $rejected = app(ClaimResolutionService::class)->review(
        $itemA->id,
        $rejected->id,
        $reviewer->id,
        ClaimStatus::REJECTED,
    );
    $itemB = makeClaimableItem($reviewer);
    $withdrawn = app(ClaimResolutionService::class)->withdraw(
        $itemB->id,
        makeClaim($itemB, $studentB)->id,
    );

    foreach ([[$itemA, $rejected], [$itemB, $withdrawn]] as [$item, $claim]) {
        expect(fn () => app(ClaimResolutionService::class)->approve(
            $item->id,
            $claim->id,
            $reviewer->id,
        ))->toThrow(InvalidArgumentException::class);
        expect($item->refresh()->approved_claim_id)->toBeNull();
    }
});

test('approval and withdrawal cannot create a pointer to a withdrawn claim', function () {
    [$reviewer, $studentA, $studentB] = makeClaimPersistenceActors(2);
    $itemA = makeClaimableItem($reviewer);
    $withdrawn = app(ClaimResolutionService::class)->withdraw(
        $itemA->id,
        makeClaim($itemA, $studentA)->id,
    );
    expect(fn () => app(ClaimResolutionService::class)->approve(
        $itemA->id,
        $withdrawn->id,
        $reviewer->id,
    ))->toThrow(InvalidArgumentException::class);

    $itemB = makeClaimableItem($reviewer);
    $approved = putClaimUnderReview($itemB, makeClaim($itemB, $studentB), $reviewer);
    $approved = app(ClaimResolutionService::class)->approve($itemB->id, $approved->id, $reviewer->id);
    expect(fn () => app(ClaimResolutionService::class)->withdraw($itemB->id, $approved->id))
        ->toThrow(DomainException::class);

    assertApprovalInvariant($itemB, $approved);
});

test('revocation atomically clears the winner pointer and records history', function () {
    [$reviewer, $student] = makeClaimPersistenceActors();
    $item = makeClaimableItem($reviewer);
    $claim = putClaimUnderReview($item, makeClaim($item, $student), $reviewer);
    $claim = app(ClaimResolutionService::class)->approve($item->id, $claim->id, $reviewer->id);
    $reviewCount = $claim->reviews()->count();

    $revoked = app(ClaimResolutionService::class)->revoke(
        $item->id,
        $claim->id,
        $reviewer->id,
        'The approval was revoked.',
        'Ownership decision corrected.',
    );

    expect($item->refresh()->approved_claim_id)->toBeNull()
        ->and($revoked->status)->toBe(ClaimStatus::REJECTED)
        ->and($revoked->reviews()->count())->toBe($reviewCount + 1)
        ->and($revoked->reviews()->latest('id')->first()->from_status)->toBe(ClaimStatus::APPROVED)
        ->and($revoked->reviews()->latest('id')->first()->to_status)->toBe(ClaimStatus::REJECTED);
});

test('a failed review insert rolls back the complete approval', function () {
    [$reviewer, $student] = makeClaimPersistenceActors();
    $item = makeClaimableItem($reviewer);
    $claim = putClaimUnderReview($item, makeClaim($item, $student), $reviewer);
    $reviewCount = $claim->reviews()->count();

    expect(fn () => app(ClaimResolutionService::class)->approve(
        $item->id,
        $claim->id,
        999999999,
    ))->toThrow(QueryException::class);

    expect($item->refresh()->approved_claim_id)->toBeNull()
        ->and($claim->refresh()->status)->toBe(ClaimStatus::UNDER_REVIEW)
        ->and($claim->reviews()->count())->toBe($reviewCount);
});

test('a failed competing transition rolls back winner pointer status and history', function () {
    [$reviewer, $studentA, $studentB] = makeClaimPersistenceActors(2);
    $item = makeClaimableItem($reviewer);
    $target = putClaimUnderReview($item, makeClaim($item, $studentA), $reviewer);
    $competing = makeClaim($item, $studentB);
    DB::table('lost_found_claims')->where('id', $competing->id)->update(['status' => 'corrupt_test_state']);
    $reviewCount = $target->reviews()->count();

    expect(fn () => app(ClaimResolutionService::class)->approve(
        $item->id,
        $target->id,
        $reviewer->id,
    ))->toThrow(ValueError::class);

    expect($item->refresh()->approved_claim_id)->toBeNull()
        ->and($target->refresh()->status)->toBe(ClaimStatus::UNDER_REVIEW)
        ->and($target->reviews()->count())->toBe($reviewCount);
});

test('approved claim pointer is unique across items', function () {
    [$reviewer, $student] = makeClaimPersistenceActors();
    $itemA = makeClaimableItem($reviewer);
    $itemB = makeClaimableItem($reviewer);
    $claim = putClaimUnderReview($itemA, makeClaim($itemA, $student), $reviewer);
    $claim = app(ClaimResolutionService::class)->approve($itemA->id, $claim->id, $reviewer->id);

    expect(fn () => DB::table('lost_found_items')->where('id', $itemB->id)->update([
        'approved_claim_id' => $claim->id,
    ]))->toThrow(QueryException::class);
});

test('claim ownership history foreign keys restrict parent deletion', function () {
    [$reviewer, $student] = makeClaimPersistenceActors();
    $item = makeClaimableItem($reviewer);
    $claim = makeClaim($item, $student);
    app(ClaimEvidenceService::class)->addTextEvidence(
        $claim,
        EvidenceType::SERIAL_FRAGMENT,
        'A non-secret serial fragment.',
    );
    putClaimUnderReview($item, $claim, $reviewer);

    expect(fn () => $item->delete())->toThrow(QueryException::class)
        ->and(fn () => $student->delete())->toThrow(QueryException::class)
        ->and(fn () => $claim->delete())->toThrow(QueryException::class)
        ->and(fn () => $reviewer->delete())->toThrow(QueryException::class);
});
