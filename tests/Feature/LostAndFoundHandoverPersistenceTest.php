<?php

use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Webkul\LostAndFound\Enums\ClaimStatus;
use Webkul\LostAndFound\Enums\CustodyEventType;
use Webkul\LostAndFound\Enums\ItemStatus;
use Webkul\LostAndFound\Models\FoundItem;
use Webkul\LostAndFound\Models\Handover;
use Webkul\LostAndFound\Models\LostFoundCategory;
use Webkul\LostAndFound\Models\LostFoundClaim;
use Webkul\LostAndFound\Repositories\LostFoundClaimRepository;
use Webkul\LostAndFound\Services\ClaimResolutionService;
use Webkul\LostAndFound\Services\CustodyService;
use Webkul\LostAndFound\Services\HandoverService;
use Webkul\Student\Models\Student;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

uses(DatabaseTransactions::class);

function makeHandoverUsers(int $count = 2): array
{
    $role = Role::create([
        'name' => 'Handover Role '.Str::random(8),
        'description' => null,
        'permission_type' => 'all',
        'permissions' => null,
    ]);

    return collect(range(1, $count))->map(fn (int $number): User => User::create([
        'name' => "Handover User {$number}",
        'email' => Str::random(12).'@example.test',
        'password' => Hash::make(Str::random(32)),
        'status' => true,
        'role_id' => $role->id,
    ]))->all();
}

function makeHandoverStudent(): Student
{
    return Student::create([
        'university_card_number' => 'HANDOVER-'.Str::random(12),
        'password' => Str::random(32),
        'name' => 'Handover Student',
    ]);
}

function makeHandoverItem(User $logger): FoundItem
{
    $category = LostFoundCategory::create(['code' => 'handover-'.Str::random(10)]);

    return FoundItem::create([
        'public_reference' => 'HANDOVER-ITEM-'.Str::random(12),
        'category_id' => $category->id,
        'logged_by_user_id' => $logger->id,
        'status' => ItemStatus::REPORTED,
        'title' => 'Handover test item',
        'found_at' => now(),
        'reported_at' => now(),
    ]);
}

function makeApprovedHandoverFixture(): array
{
    [$staff, $custodian] = makeHandoverUsers();
    $student = makeHandoverStudent();
    $item = makeHandoverItem($staff);
    app(CustodyService::class)->receive(
        $item->id,
        $staff->id,
        $custodian->id,
        'Handover Cabinet',
        CarbonImmutable::now()->subHours(2),
    );
    $claim = app(LostFoundClaimRepository::class)->create([
        'found_item_id' => $item->id,
        'claimant_student_id' => $student->id,
    ]);
    $claims = app(ClaimResolutionService::class);
    $claim = $claims->review($item->id, $claim->id, $staff->id, ClaimStatus::UNDER_REVIEW);
    $claim = $claims->approve($item->id, $claim->id, $staff->id);

    return [$item->refresh(), $claim, $student, $staff, $custodian];
}

function completeHandover(
    FoundItem $item,
    LostFoundClaim $claim,
    Student $student,
    User $staff,
): Handover {
    return app(HandoverService::class)->complete(
        $item->id,
        $claim->id,
        $student->id,
        $staff->id,
        'student card visual check',
        'Identity matched the approved claimant.',
        CarbonImmutable::now(),
    );
}

test('handover schema is focused and later-wave tables remain absent', function () {
    expect(Schema::getColumnListing('lost_found_handovers'))->toBe([
        'id', 'found_item_id', 'claim_id', 'recipient_student_id', 'staff_user_id',
        'verification_method', 'verification_note', 'handed_over_at', 'created_at', 'updated_at',
    ]);

    foreach ([
        'lost_found_locations', 'lost_found_item_attachments', 'lost_found_report_attachments',
        'lost_found_dispositions', 'potential_matches',
    ] as $table) {
        expect(Schema::hasTable($table))->toBeFalse();
    }
});

test('successful handover creates one terminal record and releases current custody', function () {
    [$item, $claim, $student, $staff, $custodian] = makeApprovedHandoverFixture();
    $custodyBefore = $item->custodyRecords()->get()->map(fn ($record): array => $record->only([
        'id', 'event_type', 'actor_user_id', 'from_custodian_user_id', 'to_custodian_user_id',
        'from_storage_location', 'to_storage_location', 'occurred_at',
    ]))->map(fn (array $record): array => array_map(
        fn ($value) => $value instanceof DateTimeInterface ? $value->format('Y-m-d H:i:s.u') : $value,
        $record,
    ));
    $handover = completeHandover($item, $claim, $student, $staff);
    $item->refresh();
    $claim->refresh();
    $history = $item->custodyRecords;
    $finalEvent = $history->last();

    expect(Handover::query()->count())->toBe(1)
        ->and($handover->found_item_id)->toBe($item->id)
        ->and($handover->claim_id)->toBe($claim->id)
        ->and($handover->recipient_student_id)->toBe($student->id)
        ->and($item->status)->toBe(ItemStatus::RETURNED)
        ->and($item->approved_claim_id)->toBe($claim->id)
        ->and($claim->status)->toBe(ClaimStatus::APPROVED)
        ->and($item->current_custodian_user_id)->toBeNull()
        ->and($item->current_storage_location)->toBeNull()
        ->and($item->custody_started_at)->toBeNull()
        ->and($item->custody_changed_at)->toBeNull()
        ->and($history)->toHaveCount(2)
        ->and($history->take($custodyBefore->count())->map(fn ($record): array => $record->only([
            'id', 'event_type', 'actor_user_id', 'from_custodian_user_id', 'to_custodian_user_id',
            'from_storage_location', 'to_storage_location', 'occurred_at',
        ]))->map(fn (array $record): array => array_map(
            fn ($value) => $value instanceof DateTimeInterface ? $value->format('Y-m-d H:i:s.u') : $value,
            $record,
        ))->values()->all())->toBe($custodyBefore->values()->all())
        ->and($finalEvent->event_type)->toBe(CustodyEventType::HANDED_OVER)
        ->and($finalEvent->actor_user_id)->toBe($staff->id)
        ->and($finalEvent->from_custodian_user_id)->toBe($custodian->id)
        ->and($finalEvent->to_custodian_user_id)->toBeNull()
        ->and($finalEvent->from_storage_location)->toBe('Handover Cabinet')
        ->and($finalEvent->to_storage_location)->toBeNull()
        ->and($finalEvent->occurred_at->equalTo($handover->handed_over_at))->toBeTrue()
        ->and($item->handover->is($handover))->toBeTrue()
        ->and($claim->handover->is($handover))->toBeTrue()
        ->and($handover->recipient->is($student))->toBeTrue()
        ->and($handover->staff->is($staff))->toBeTrue();
});

test('handover fails without an approved claim and preserves custody', function () {
    [$staff, $custodian] = makeHandoverUsers();
    $student = makeHandoverStudent();
    $item = makeHandoverItem($staff);
    app(CustodyService::class)->receive($item->id, $staff->id, $custodian->id, 'No Claim Cabinet');

    expect(fn () => app(HandoverService::class)->complete(
        $item->id,
        999999999,
        $student->id,
        $staff->id,
        'student card visual check',
    ))->toThrow(DomainException::class);

    expect(Handover::query()->count())->toBe(0)
        ->and($item->refresh()->status)->toBe(ItemStatus::IN_CUSTODY)
        ->and($item->current_custodian_user_id)->toBe($custodian->id)
        ->and($item->custodyRecords()->count())->toBe(1);
});

test('claim pointer and status mismatch blocks handover without repair', function () {
    [$item, $claim, $student, $staff] = makeApprovedHandoverFixture();
    DB::table('lost_found_claims')->where('id', $claim->id)->update([
        'status' => ClaimStatus::UNDER_REVIEW->value,
    ]);

    expect(fn () => completeHandover($item, $claim, $student, $staff))
        ->toThrow(DomainException::class);

    expect(Handover::query()->count())->toBe(0)
        ->and($item->refresh()->status)->toBe(ItemStatus::IN_CUSTODY)
        ->and($item->approved_claim_id)->toBe($claim->id);
});

test('claim belonging to another item cannot authorize handover', function () {
    [$itemA, $claimA, $studentA, $staff] = makeApprovedHandoverFixture();
    [$itemB, $claimB, $studentB] = makeApprovedHandoverFixture();

    expect(fn () => app(HandoverService::class)->complete(
        $itemA->id,
        $claimB->id,
        $studentB->id,
        $staff->id,
        'student card visual check',
    ))->toThrow(DomainException::class);

    expect($itemA->refresh()->status)->toBe(ItemStatus::IN_CUSTODY)
        ->and($itemA->approved_claim_id)->toBe($claimA->id)
        ->and($itemB->refresh()->status)->toBe(ItemStatus::IN_CUSTODY)
        ->and(Handover::query()->count())->toBe(0);
});

test('recipient must be the authoritative approved claimant', function () {
    [$item, $claim, $student, $staff] = makeApprovedHandoverFixture();
    $wrongRecipient = makeHandoverStudent();

    expect(fn () => app(HandoverService::class)->complete(
        $item->id,
        $claim->id,
        $wrongRecipient->id,
        $staff->id,
        'student card visual check',
    ))->toThrow(DomainException::class);

    expect(Handover::query()->count())->toBe(0)
        ->and($item->refresh()->status)->toBe(ItemStatus::IN_CUSTODY)
        ->and($claim->refresh()->claimant_student_id)->toBe($student->id);
});

test('approved claim without current custody cannot be handed over', function () {
    [$staff] = makeHandoverUsers(1);
    $student = makeHandoverStudent();
    $item = makeHandoverItem($staff);
    $claim = app(LostFoundClaimRepository::class)->create([
        'found_item_id' => $item->id,
        'claimant_student_id' => $student->id,
    ]);
    $claims = app(ClaimResolutionService::class);
    $claim = $claims->review($item->id, $claim->id, $staff->id, ClaimStatus::UNDER_REVIEW);
    $claim = $claims->approve($item->id, $claim->id, $staff->id);

    expect(fn () => completeHandover($item, $claim, $student, $staff))
        ->toThrow(DomainException::class);
    expect($item->refresh()->status)->toBe(ItemStatus::REPORTED)
        ->and(Handover::query()->count())->toBe(0);
});

test('inactive staff cannot complete handover', function () {
    [$item, $claim, $student, $staff] = makeApprovedHandoverFixture();
    $staff->update(['status' => false]);

    expect(fn () => completeHandover($item, $claim, $student, $staff))
        ->toThrow(DomainException::class);
    expect($item->refresh()->status)->toBe(ItemStatus::IN_CUSTODY)
        ->and(Handover::query()->count())->toBe(0)
        ->and($item->custodyRecords()->count())->toBe(1);
});

test('duplicate and stale handover attempts produce one terminal outcome', function () {
    [$item, $claim, $student, $staff] = makeApprovedHandoverFixture();
    completeHandover($item, $claim, $student, $staff);

    expect(fn () => completeHandover($item, $claim, $student, $staff))
        ->toThrow(DomainException::class);

    expect(Handover::query()->count())->toBe(1)
        ->and($item->refresh()->status)->toBe(ItemStatus::RETURNED)
        ->and($item->custodyRecords()->where('event_type', CustodyEventType::HANDED_OVER->value)->count())->toBe(1);
});

test('item return failure rolls back handover and final custody history', function () {
    [$item, $claim, $student, $staff, $custodian] = makeApprovedHandoverFixture();
    DB::unprepared(sprintf(
        "CREATE TRIGGER handover_return_failure BEFORE UPDATE OF status ON lost_found_items WHEN NEW.id = %d AND NEW.status = 'returned' BEGIN SELECT RAISE(ABORT, 'forced handover return failure'); END",
        $item->id,
    ));

    try {
        expect(fn () => completeHandover($item, $claim, $student, $staff))
            ->toThrow(QueryException::class);
    } finally {
        DB::unprepared('DROP TRIGGER IF EXISTS handover_return_failure');
    }

    expect(Handover::query()->count())->toBe(0)
        ->and($item->refresh()->status)->toBe(ItemStatus::IN_CUSTODY)
        ->and($item->current_custodian_user_id)->toBe($custodian->id)
        ->and($item->current_storage_location)->toBe('Handover Cabinet')
        ->and($item->custodyRecords()->count())->toBe(1)
        ->and($claim->refresh()->status)->toBe(ClaimStatus::APPROVED);
});

test('handover and final custody history are immutable', function () {
    [$item, $claim, $student, $staff] = makeApprovedHandoverFixture();
    $handover = completeHandover($item, $claim, $student, $staff);
    $handover->verification_note = 'Attempted rewrite';

    expect(fn () => $handover->save())->toThrow(LogicException::class);
    expect(fn () => Handover::findOrFail($handover->id)->delete())->toThrow(LogicException::class);

    $event = $item->custodyRecords()->where('event_type', CustodyEventType::HANDED_OVER->value)->firstOrFail();
    $event->notes = 'Attempted rewrite';
    expect(fn () => $event->save())->toThrow(LogicException::class);
});

test('claim revocation and replacement approval are blocked after handover', function () {
    [$item, $claim, $student, $staff] = makeApprovedHandoverFixture();
    completeHandover($item, $claim, $student, $staff);
    $otherStudent = makeHandoverStudent();
    $otherClaim = LostFoundClaim::create([
        'found_item_id' => $item->id,
        'claimant_student_id' => $otherStudent->id,
        'status' => ClaimStatus::UNDER_REVIEW,
        'submitted_at' => now(),
    ]);
    $claims = app(ClaimResolutionService::class);

    expect(fn () => $claims->revoke($item->id, $claim->id, $staff->id))
        ->toThrow(DomainException::class);
    expect(fn () => $claims->approve($item->id, $otherClaim->id, $staff->id))
        ->toThrow(DomainException::class);

    expect($item->refresh()->approved_claim_id)->toBe($claim->id)
        ->and($claim->refresh()->status)->toBe(ClaimStatus::APPROVED)
        ->and($item->status)->toBe(ItemStatus::RETURNED);
});

test('returned item cannot reopen institutional custody', function () {
    [$item, $claim, $student, $staff, $custodian] = makeApprovedHandoverFixture();
    completeHandover($item, $claim, $student, $staff);
    $custody = app(CustodyService::class);

    expect(fn () => $custody->receive($item->id, $staff->id, $custodian->id, 'Reopened Cabinet'))
        ->toThrow(DomainException::class);
    expect(fn () => $custody->transfer(
        $item->id,
        $staff->id,
        $custodian->id,
        'Handover Cabinet',
        $staff->id,
        'Reopened Cabinet',
    ))->toThrow(DomainException::class);
    expect(fn () => $custody->moveStorage(
        $item->id,
        $staff->id,
        $custodian->id,
        'Handover Cabinet',
        'Reopened Cabinet',
    ))->toThrow(DomainException::class);

    expect($item->custodyRecords()->count())->toBe(2)
        ->and($item->refresh()->status)->toBe(ItemStatus::RETURNED);
});

test('handover timestamps cannot predate custody or be in the future', function () {
    [$item, $claim, $student, $staff] = makeApprovedHandoverFixture();

    foreach ([$item->custody_changed_at->subSecond(), CarbonImmutable::now()->addMinute()] as $invalidTime) {
        expect(fn () => app(HandoverService::class)->complete(
            $item->id,
            $claim->id,
            $student->id,
            $staff->id,
            'student card visual check',
            null,
            $invalidTime,
        ))->toThrow(DomainException::class);
    }

    expect(Handover::query()->count())->toBe(0)
        ->and($item->refresh()->status)->toBe(ItemStatus::IN_CUSTODY)
        ->and($item->custodyRecords()->count())->toBe(1);
});

test('handover verification is encrypted hidden and rejects authentication secrets', function () {
    [$item, $claim, $student, $staff] = makeApprovedHandoverFixture();

    expect(fn () => app(HandoverService::class)->complete(
        $item->id,
        $claim->id,
        $student->id,
        $staff->id,
        'password confirmation',
        'Authentication secrets are forbidden.',
    ))->toThrow(InvalidArgumentException::class);

    $handover = completeHandover($item, $claim, $student, $staff);
    $raw = DB::table('lost_found_handovers')->find($handover->id);

    expect($raw->verification_method)->not->toBe('student card visual check')
        ->and($raw->verification_note)->not->toBe('Identity matched the approved claimant.')
        ->and($handover->verification_method)->toBe('student card visual check')
        ->and($handover->toArray())->not->toHaveKeys([
            'recipient_student_id', 'staff_user_id', 'verification_method', 'verification_note',
        ]);
});

test('handover foreign keys restrict history deletion and database uniqueness is enforced', function () {
    [$item, $claim, $student, $staff] = makeApprovedHandoverFixture();
    $handover = completeHandover($item, $claim, $student, $staff);
    $otherItem = makeHandoverItem($staff);

    expect(fn () => Handover::create([
        'found_item_id' => $item->id,
        'claim_id' => $claim->id,
        'recipient_student_id' => $student->id,
        'staff_user_id' => $staff->id,
        'verification_method' => 'duplicate item',
        'handed_over_at' => now(),
    ]))->toThrow(QueryException::class);
    expect(fn () => Handover::create([
        'found_item_id' => $otherItem->id,
        'claim_id' => $claim->id,
        'recipient_student_id' => $student->id,
        'staff_user_id' => $staff->id,
        'verification_method' => 'duplicate claim',
        'handed_over_at' => now(),
    ]))->toThrow(QueryException::class);
    expect(fn () => $item->delete())->toThrow(QueryException::class)
        ->and(fn () => $claim->delete())->toThrow(QueryException::class)
        ->and(fn () => $student->delete())->toThrow(QueryException::class)
        ->and(fn () => $staff->delete())->toThrow(QueryException::class)
        ->and($handover->exists)->toBeTrue();
});

test('only handover returns an approved item in custody', function () {
    [$item, $claim, $student, $staff, $custodian] = makeApprovedHandoverFixture();

    expect($item->status)->toBe(ItemStatus::IN_CUSTODY)
        ->and($claim->status)->toBe(ClaimStatus::APPROVED);

    $secondCustodian = makeHandoverUsers(1)[0];
    app(CustodyService::class)->transfer(
        $item->id,
        $staff->id,
        $custodian->id,
        'Handover Cabinet',
        $secondCustodian->id,
        'Final Cabinet',
    );
    expect($item->refresh()->status)->toBe(ItemStatus::IN_CUSTODY);

    completeHandover($item, $claim, $student, $staff);
    expect($item->refresh()->status)->toBe(ItemStatus::RETURNED);
});
