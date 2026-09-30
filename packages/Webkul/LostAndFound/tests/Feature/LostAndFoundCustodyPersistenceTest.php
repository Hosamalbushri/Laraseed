<?php

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Webkul\LostAndFound\Enums\ClaimStatus;
use Webkul\LostAndFound\Enums\CustodyEventType;
use Webkul\LostAndFound\Enums\ItemStatus;
use Webkul\LostAndFound\Models\CustodyRecord;
use Webkul\LostAndFound\Models\FoundItem;
use Webkul\LostAndFound\Models\LostFoundCategory;
use Webkul\LostAndFound\Repositories\FoundItemRepository;
use Webkul\LostAndFound\Repositories\LostFoundClaimRepository;
use Webkul\LostAndFound\Services\ClaimResolutionService;
use Webkul\LostAndFound\Services\CustodyService;
use Webkul\Student\Models\Student;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

uses(DatabaseTransactions::class);

function makeCustodyUsers(int $count = 3): array
{
    $role = Role::create([
        'name' => 'Custody Role '.Str::random(8),
        'description' => null,
        'permission_type' => 'all',
        'permissions' => null,
    ]);

    return collect(range(1, $count))->map(fn (int $number): User => User::create([
        'name' => "Custody User {$number}",
        'email' => Str::random(12).'@example.test',
        'password' => Hash::make(Str::random(32)),
        'status' => true,
        'role_id' => $role->id,
    ]))->all();
}

function makeCustodyItem(User $logger, ItemStatus $status = ItemStatus::REPORTED): FoundItem
{
    $category = LostFoundCategory::create(['code' => 'custody-'.Str::random(10)]);

    return FoundItem::create([
        'public_reference' => 'CUSTODY-'.Str::random(12),
        'category_id' => $category->id,
        'logged_by_user_id' => $logger->id,
        'status' => $status,
        'title' => 'Custody test item',
        'found_at' => now(),
        'reported_at' => $status === ItemStatus::REPORTED ? now() : null,
    ]);
}

function custodyTime(string $time): CarbonImmutable
{
    return CarbonImmutable::parse($time, 'UTC');
}

test('custody schema contains the history and current projection only', function () {
    expect(Schema::getColumnListing('lost_found_custody_records'))->toBe([
        'id', 'found_item_id', 'event_type', 'actor_user_id', 'from_custodian_user_id',
        'to_custodian_user_id', 'from_storage_location', 'to_storage_location', 'notes',
        'occurred_at', 'created_at', 'updated_at',
    ])->and(Schema::getColumnListing('lost_found_items'))->toContain(
        'approved_claim_id',
        'current_custodian_user_id',
        'current_storage_location',
        'custody_started_at',
        'custody_changed_at',
    );

    foreach ([
        'lost_found_locations', 'lost_found_item_attachments',
        'lost_found_report_attachments', 'lost_found_dispositions', 'potential_matches',
    ] as $table) {
        expect(Schema::hasTable($table))->toBeFalse();
    }
});

test('reported item is received into custody with a consistent projection and history', function () {
    [$actor, $custodian] = makeCustodyUsers(2);
    $item = makeCustodyItem($actor);
    $time = custodyTime('2026-09-27 10:00:00');

    $received = app(CustodyService::class)->receive(
        $item->id,
        $actor->id,
        $custodian->id,
        'Security Office Shelf 3',
        $time,
        'Received and secured.',
    );
    $record = $received->custodyRecords->sole();

    expect($received->status)->toBe(ItemStatus::IN_CUSTODY)
        ->and($received->current_custodian_user_id)->toBe($custodian->id)
        ->and($received->current_storage_location)->toBe('Security Office Shelf 3')
        ->and($received->custody_started_at->equalTo($time))->toBeTrue()
        ->and($received->custody_changed_at->equalTo($time))->toBeTrue()
        ->and($record->event_type)->toBe(CustodyEventType::LOGGED)
        ->and($record->from_custodian_user_id)->toBeNull()
        ->and($record->to_custodian_user_id)->toBe($custodian->id)
        ->and($record->to_storage_location)->toBe('Security Office Shelf 3')
        ->and($received->currentCustodian->is($custodian))->toBeTrue()
        ->and($record->foundItem->is($received))->toBeTrue()
        ->and($record->actor->is($actor))->toBeTrue()
        ->and($record->toCustodian->is($custodian))->toBeTrue();
});

test('custody transfer appends history and updates the current projection', function () {
    [$actor, $custodianA, $custodianB] = makeCustodyUsers();
    $item = makeCustodyItem($actor);
    $service = app(CustodyService::class);
    $service->receive($item->id, $actor->id, $custodianA->id, 'Cabinet A', custodyTime('2026-09-27 10:00:00'));

    $transferred = $service->transfer(
        $item->id,
        $actor->id,
        $custodianA->id,
        'Cabinet A',
        $custodianB->id,
        'Cabinet B',
        custodyTime('2026-09-27 11:00:00'),
    );
    $history = $transferred->custodyRecords;

    expect($history)->toHaveCount(2)
        ->and($history[0]->event_type)->toBe(CustodyEventType::LOGGED)
        ->and($history[1]->event_type)->toBe(CustodyEventType::TRANSFERRED)
        ->and($history[1]->from_custodian_user_id)->toBe($custodianA->id)
        ->and($history[1]->to_custodian_user_id)->toBe($custodianB->id)
        ->and($transferred->current_custodian_user_id)->toBe($custodianB->id)
        ->and($transferred->current_storage_location)->toBe('Cabinet B')
        ->and($transferred->custody_started_at->equalTo(custodyTime('2026-09-27 10:00:00')))->toBeTrue()
        ->and($transferred->custody_changed_at->equalTo(custodyTime('2026-09-27 11:00:00')))->toBeTrue();
});

test('storage move preserves the custodian and records a distinct event', function () {
    [$actor, $custodian] = makeCustodyUsers(2);
    $item = makeCustodyItem($actor);
    $service = app(CustodyService::class);
    $service->receive($item->id, $actor->id, $custodian->id, 'Drawer 1', custodyTime('2026-09-27 10:00:00'));

    $moved = $service->moveStorage(
        $item->id,
        $actor->id,
        $custodian->id,
        'Drawer 1',
        'Drawer 2',
        custodyTime('2026-09-27 10:30:00'),
    );
    $record = $moved->custodyRecords->last();

    expect($record->event_type)->toBe(CustodyEventType::STORAGE_LOCATION_CHANGED)
        ->and($record->from_custodian_user_id)->toBe($custodian->id)
        ->and($record->to_custodian_user_id)->toBe($custodian->id)
        ->and($record->from_storage_location)->toBe('Drawer 1')
        ->and($record->to_storage_location)->toBe('Drawer 2')
        ->and($moved->current_custodian_user_id)->toBe($custodian->id)
        ->and($moved->current_storage_location)->toBe('Drawer 2');
});

test('custody history is append-only through the model API', function () {
    [$actor, $custodian] = makeCustodyUsers(2);
    $item = app(CustodyService::class)->receive(
        makeCustodyItem($actor)->id,
        $actor->id,
        $custodian->id,
        'Archive Shelf',
    );
    $record = $item->custodyRecords->sole();
    $record->notes = 'Attempted rewrite';

    expect(fn () => $record->save())->toThrow(LogicException::class);
    expect(fn () => CustodyRecord::findOrFail($record->id)->delete())->toThrow(LogicException::class);
});

test('stale and duplicate transfer attempts cannot append false history', function () {
    [$actor, $custodianA, $custodianB, $custodianC] = makeCustodyUsers(4);
    $item = makeCustodyItem($actor);
    $service = app(CustodyService::class);
    $service->receive($item->id, $actor->id, $custodianA->id, 'Shelf A');
    $service->transfer($item->id, $actor->id, $custodianA->id, 'Shelf A', $custodianB->id, 'Shelf B');

    expect(fn () => $service->transfer(
        $item->id,
        $actor->id,
        $custodianA->id,
        'Shelf A',
        $custodianC->id,
        'Shelf C',
    ))->toThrow(DomainException::class);
    expect(fn () => $service->transfer(
        $item->id,
        $actor->id,
        $custodianA->id,
        'Shelf A',
        $custodianB->id,
        'Shelf B',
    ))->toThrow(DomainException::class);

    expect($item->refresh()->current_custodian_user_id)->toBe($custodianB->id)
        ->and($item->current_storage_location)->toBe('Shelf B')
        ->and($item->custodyRecords()->count())->toBe(2);
});

test('no-op and out-of-order custody events are rejected', function () {
    [$actor, $custodian] = makeCustodyUsers(2);
    $item = makeCustodyItem($actor);
    $service = app(CustodyService::class);
    $service->receive(
        $item->id,
        $actor->id,
        $custodian->id,
        'Locker 1',
        custodyTime('2026-09-27 12:00:00'),
    );

    expect(fn () => $service->moveStorage(
        $item->id,
        $actor->id,
        $custodian->id,
        'Locker 1',
        'Locker 1',
    ))->toThrow(InvalidArgumentException::class);
    expect(fn () => $service->moveStorage(
        $item->id,
        $actor->id,
        $custodian->id,
        'Locker 1',
        'Locker 2',
        custodyTime('2026-09-27 11:59:59'),
    ))->toThrow(DomainException::class);

    expect($item->custodyRecords()->count())->toBe(1)
        ->and($item->refresh()->current_storage_location)->toBe('Locker 1');
});

test('invalid and inactive users cannot receive custody responsibility', function () {
    [$actor, $custodian] = makeCustodyUsers(2);
    $inactive = User::create([
        'name' => 'Inactive Custodian',
        'email' => Str::random(12).'@example.test',
        'password' => Hash::make(Str::random(32)),
        'status' => false,
        'role_id' => $actor->role_id,
    ]);
    $service = app(CustodyService::class);

    expect(fn () => $service->receive(
        999999999,
        $actor->id,
        $custodian->id,
        'Missing Item',
    ))->toThrow(ModelNotFoundException::class);

    foreach ([999999999, $inactive->id] as $invalidCustodianId) {
        $item = makeCustodyItem($actor);
        expect(fn () => $service->receive(
            $item->id,
            $actor->id,
            $invalidCustodianId,
            'Secure Room',
        ))->toThrow(DomainException::class);
        expect($item->refresh()->status)->toBe(ItemStatus::REPORTED)
            ->and($item->custodyRecords()->count())->toBe(0);
    }

    $item = makeCustodyItem($actor);
    expect(fn () => $service->receive(
        $item->id,
        $inactive->id,
        $custodian->id,
        'Secure Room',
    ))->toThrow(DomainException::class);
});

test('terminal items cannot undergo internal custody operations', function (ItemStatus $terminalStatus) {
    [$actor, $custodianA, $custodianB] = makeCustodyUsers();
    $item = makeCustodyItem($actor);
    $service = app(CustodyService::class);
    $service->receive($item->id, $actor->id, $custodianA->id, 'Terminal Shelf');
    DB::table('lost_found_items')->where('id', $item->id)->update(['status' => $terminalStatus->value]);

    expect(fn () => $service->transfer(
        $item->id,
        $actor->id,
        $custodianA->id,
        'Terminal Shelf',
        $custodianB->id,
        'Other Shelf',
    ))->toThrow(DomainException::class);
    expect($item->custodyRecords()->count())->toBe(1);
})->with([ItemStatus::RETURNED, ItemStatus::DISPOSED]);

test('projection update failure rolls back receipt history and item state', function () {
    [$actor, $custodian] = makeCustodyUsers(2);
    $item = makeCustodyItem($actor);
    DB::unprepared(sprintf(
        "CREATE TRIGGER custody_projection_failure BEFORE UPDATE OF current_custodian_user_id ON lost_found_items WHEN NEW.id = %d BEGIN SELECT RAISE(ABORT, 'forced custody projection failure'); END",
        $item->id,
    ));

    try {
        expect(fn () => app(CustodyService::class)->receive(
            $item->id,
            $actor->id,
            $custodian->id,
            'Rollback Shelf',
        ))->toThrow(QueryException::class);
    } finally {
        DB::unprepared('DROP TRIGGER IF EXISTS custody_projection_failure');
    }

    expect($item->refresh()->status)->toBe(ItemStatus::REPORTED)
        ->and($item->current_custodian_user_id)->toBeNull()
        ->and($item->current_storage_location)->toBeNull()
        ->and($item->custodyRecords()->count())->toBe(0);
});

test('claim approval transfer and revocation remain independent from custody', function () {
    [$reviewer, $custodianA, $custodianB] = makeCustodyUsers();
    $item = makeCustodyItem($reviewer);
    $custody = app(CustodyService::class);
    $custody->receive($item->id, $reviewer->id, $custodianA->id, 'Claim Shelf');
    $student = Student::create([
        'university_card_number' => 'CUSTODY-'.Str::random(12),
        'password' => Str::random(32),
        'name' => 'Custody Claimant',
    ]);
    $claim = app(LostFoundClaimRepository::class)->create([
        'found_item_id' => $item->id,
        'claimant_student_id' => $student->id,
    ]);
    $claims = app(ClaimResolutionService::class);
    $claim = $claims->review($item->id, $claim->id, $reviewer->id, ClaimStatus::UNDER_REVIEW);
    $claim = $claims->approve($item->id, $claim->id, $reviewer->id);

    expect($item->refresh()->current_custodian_user_id)->toBe($custodianA->id)
        ->and($item->current_storage_location)->toBe('Claim Shelf')
        ->and($item->custodyRecords()->count())->toBe(1);

    $custody->transfer(
        $item->id,
        $reviewer->id,
        $custodianA->id,
        'Claim Shelf',
        $custodianB->id,
        'Review Shelf',
    );
    expect($item->refresh()->approved_claim_id)->toBe($claim->id)
        ->and($claim->refresh()->status)->toBe(ClaimStatus::APPROVED);

    $claims->revoke($item->id, $claim->id, $reviewer->id);
    expect($item->refresh()->current_custodian_user_id)->toBe($custodianB->id)
        ->and($item->current_storage_location)->toBe('Review Shelf')
        ->and($item->custodyRecords()->count())->toBe(2);
});

test('institutional custody does not require a claim or expose handover through custody service', function () {
    [$actor, $custodian] = makeCustodyUsers(2);
    $item = app(CustodyService::class)->receive(
        makeCustodyItem($actor)->id,
        $actor->id,
        $custodian->id,
        'Unclaimed Shelf',
    );

    expect($item->claims()->count())->toBe(0)
        ->and($item->approved_claim_id)->toBeNull()
        ->and($item->status)->toBe(ItemStatus::IN_CUSTODY)
        ->and(method_exists(CustodyService::class, 'handOver'))->toBeFalse();
});

test('custody locations and notes are encrypted in history and hidden from serialization', function () {
    [$actor, $custodian] = makeCustodyUsers(2);
    $item = app(CustodyService::class)->receive(
        makeCustodyItem($actor)->id,
        $actor->id,
        $custodian->id,
        'Private Cabinet 14',
        null,
        'Restricted operational note.',
    );
    $record = $item->custodyRecords->sole();
    $raw = DB::table('lost_found_custody_records')->find($record->id);

    expect($raw->to_storage_location)->not->toBe('Private Cabinet 14')
        ->and($raw->notes)->not->toBe('Restricted operational note.')
        ->and($record->to_storage_location)->toBe('Private Cabinet 14')
        ->and($record->notes)->toBe('Restricted operational note.')
        ->and($record->toArray())->not->toHaveKeys([
            'actor_user_id', 'from_custodian_user_id', 'to_custodian_user_id',
            'from_storage_location', 'to_storage_location', 'notes',
        ])
        ->and($item->toArray())->not->toHaveKeys([
            'current_custodian_user_id', 'current_storage_location',
            'custody_started_at', 'custody_changed_at',
        ]);
});

test('custody foreign keys and delete restrictions preserve history', function () {
    [$actor, $custodian] = makeCustodyUsers(2);
    $item = app(CustodyService::class)->receive(
        makeCustodyItem($actor)->id,
        $actor->id,
        $custodian->id,
        'History Shelf',
    );

    expect(fn () => CustodyRecord::create([
        'found_item_id' => 999999999,
        'event_type' => CustodyEventType::LOGGED,
        'actor_user_id' => $actor->id,
        'to_custodian_user_id' => $custodian->id,
        'to_storage_location' => 'Invalid Item',
        'occurred_at' => now(),
    ]))->toThrow(QueryException::class);
    expect(fn () => CustodyRecord::create([
        'found_item_id' => $item->id,
        'event_type' => CustodyEventType::TRANSFERRED,
        'actor_user_id' => 999999999,
        'to_custodian_user_id' => $custodian->id,
        'to_storage_location' => 'Invalid Actor',
        'occurred_at' => now(),
    ]))->toThrow(QueryException::class);
    expect(fn () => CustodyRecord::create([
        'found_item_id' => $item->id,
        'event_type' => CustodyEventType::TRANSFERRED,
        'actor_user_id' => $actor->id,
        'to_custodian_user_id' => 999999999,
        'to_storage_location' => 'Invalid Custodian',
        'occurred_at' => now(),
    ]))->toThrow(QueryException::class);
    expect(fn () => $item->delete())->toThrow(QueryException::class)
        ->and(fn () => $actor->delete())->toThrow(QueryException::class)
        ->and(fn () => $custodian->delete())->toThrow(QueryException::class);
});

test('generic found item repository cannot alter custody projection', function () {
    [$actor, $custodian] = makeCustodyUsers(2);
    $item = makeCustodyItem($actor);

    expect(fn () => app(FoundItemRepository::class)->update([
        'current_custodian_user_id' => $custodian->id,
        'current_storage_location' => 'Bypass Shelf',
    ], $item->id))->toThrow(LogicException::class);
});
