<?php

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Webkul\LostAndFound\Enums\ItemStatus;
use Webkul\LostAndFound\Enums\ReportStatus;
use Webkul\LostAndFound\Models\FoundItem;
use Webkul\LostAndFound\Models\LostFoundCategory;
use Webkul\LostAndFound\Models\LostReport;
use Webkul\LostAndFound\Repositories\FoundItemRepository;
use Webkul\LostAndFound\Repositories\LostFoundCategoryRepository;
use Webkul\LostAndFound\Repositories\LostReportRepository;
use Webkul\Student\Models\Student;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

uses(DatabaseTransactions::class);

function createLostFoundPersistenceActors(): array
{
    $role = Role::create([
        'name' => 'Lost Found Test '.Str::random(8),
        'description' => null,
        'permission_type' => 'all',
        'permissions' => null,
    ]);

    $user = User::create([
        'name' => 'Lost Found Employee',
        'email' => Str::random(12).'@example.test',
        'password' => Hash::make(Str::random(32)),
        'status' => true,
        'role_id' => $role->id,
    ]);

    $student = Student::create([
        'university_card_number' => 'LF-'.Str::random(12),
        'password' => Str::random(32),
        'name' => 'Lost Found Student',
    ]);

    return [$user, $student];
}

function createFoundItemForPersistenceTest(User $user, LostFoundCategory $category, string $reference): FoundItem
{
    return FoundItem::create([
        'public_reference' => $reference,
        'category_id' => $category->id,
        'logged_by_user_id' => $user->id,
        'status' => ItemStatus::REPORTED,
        'title' => 'Found phone',
        'public_description' => 'A black phone in a protective case.',
        'found_location' => 'Library entrance',
        'found_at' => now(),
        'reported_at' => now(),
    ]);
}

test('wave one foundation remains intact beneath claim persistence', function () {
    foreach (['lost_found_categories', 'lost_found_items', 'lost_found_item_private_details', 'lost_found_reports'] as $table) {
        expect(Schema::hasTable($table))->toBeTrue();
    }

    expect(Schema::getColumnListing('lost_found_categories'))->toBe([
        'id', 'code', 'is_active', 'sort_order', 'created_at', 'updated_at',
    ])->and(Schema::getColumnListing('lost_found_items'))->toBe([
        'id', 'public_reference', 'public_reference_key', 'category_id', 'logged_by_user_id',
        'status', 'title', 'public_description', 'found_location', 'found_at', 'reported_at',
        'created_at', 'updated_at', 'approved_claim_id', 'current_custodian_user_id',
        'current_storage_location', 'custody_started_at', 'custody_changed_at',
    ])->and(Schema::getColumnListing('lost_found_item_private_details'))->toBe([
        'id', 'found_item_id', 'identifying_details', 'serial_fragment', 'staff_notes', 'created_at', 'updated_at',
    ])->and(Schema::getColumnListing('lost_found_reports'))->toBe([
        'id', 'public_reference', 'public_reference_key', 'student_id', 'category_id',
        'resolved_found_item_id', 'status', 'title', 'public_description', 'private_description',
        'lost_location', 'lost_at', 'submitted_at', 'closed_at', 'created_at', 'updated_at',
    ]);

    foreach ([
    ] as $table) {
        expect(Schema::hasTable($table))->toBeFalse();
    }
});

test('aggregate relationships resolve existing campus identities and private details', function () {
    [$user, $student] = createLostFoundPersistenceActors();
    $category = LostFoundCategory::create(['code' => 'phone', 'sort_order' => 10]);
    $item = createFoundItemForPersistenceTest($user, $category, 'ITEM-'.Str::random(10));
    $detail = $item->privateDetail()->create([
        'identifying_details' => 'Hidden identifying mark',
        'serial_fragment' => 'A1B2',
        'staff_notes' => 'Restricted staff note',
    ]);
    $report = LostReport::create([
        'public_reference' => 'REPORT-'.Str::random(10),
        'student_id' => $student->id,
        'category_id' => $category->id,
        'resolved_found_item_id' => $item->id,
        'status' => ReportStatus::RESOLVED,
        'title' => 'Lost phone',
        'private_description' => 'Private ownership detail',
    ]);

    expect($item->category->is($category))->toBeTrue()
        ->and($item->loggedBy->is($user))->toBeTrue()
        ->and($item->privateDetail->is($detail))->toBeTrue()
        ->and($detail->foundItem->is($item))->toBeTrue()
        ->and($report->student->is($student))->toBeTrue()
        ->and($report->category->is($category))->toBeTrue()
        ->and($report->resolvedFoundItem->is($item))->toBeTrue()
        ->and($item->status)->toBe(ItemStatus::REPORTED)
        ->and($report->status)->toBe(ReportStatus::RESOLVED);
});

test('case equivalent public references collide on the normalized key', function () {
    [$user, $student] = createLostFoundPersistenceActors();
    $category = LostFoundCategory::create(['code' => 'device']);

    createFoundItemForPersistenceTest($user, $category, 'ABC-123');

    expect(fn () => createFoundItemForPersistenceTest($user, $category, 'abc-123'))
        ->toThrow(QueryException::class);

    LostReport::create([
        'public_reference' => 'REPORT-ABC',
        'student_id' => $student->id,
        'status' => ReportStatus::DRAFT,
        'title' => 'First report',
    ]);

    expect(fn () => LostReport::create([
        'public_reference' => 'report-abc',
        'student_id' => $student->id,
        'status' => ReportStatus::DRAFT,
        'title' => 'Second report',
    ]))->toThrow(QueryException::class);
});

test('category codes and private details enforce uniqueness', function () {
    [$user] = createLostFoundPersistenceActors();
    $category = LostFoundCategory::create(['code' => 'wallet']);
    $item = createFoundItemForPersistenceTest($user, $category, 'ITEM-'.Str::random(10));

    expect(fn () => LostFoundCategory::create(['code' => 'wallet']))
        ->toThrow(QueryException::class);

    $item->privateDetail()->create(['identifying_details' => 'First detail']);

    expect(fn () => $item->privateDetail()->create(['identifying_details' => 'Second detail']))
        ->toThrow(QueryException::class);
});

test('sensitive model fields are encrypted at rest and hidden from serialization', function () {
    [$user, $student] = createLostFoundPersistenceActors();
    $category = LostFoundCategory::create(['code' => 'document']);
    $item = createFoundItemForPersistenceTest($user, $category, 'ITEM-'.Str::random(10));

    $detail = $item->privateDetail()->create([
        'identifying_details' => 'private-identifying-value',
        'serial_fragment' => 'private-fragment-value',
        'staff_notes' => 'private-staff-value',
    ]);
    $report = LostReport::create([
        'public_reference' => 'REPORT-'.Str::random(10),
        'student_id' => $student->id,
        'category_id' => $category->id,
        'status' => ReportStatus::ACTIVE,
        'title' => 'Lost document',
        'private_description' => 'private-report-value',
    ]);

    $rawDetail = DB::table('lost_found_item_private_details')->find($detail->id);
    $rawReport = DB::table('lost_found_reports')->find($report->id);

    expect($rawDetail->identifying_details)->not->toBe('private-identifying-value')
        ->and($rawDetail->serial_fragment)->not->toBe('private-fragment-value')
        ->and($rawDetail->staff_notes)->not->toBe('private-staff-value')
        ->and($rawReport->private_description)->not->toBe('private-report-value')
        ->and($detail->identifying_details)->toBe('private-identifying-value')
        ->and($report->private_description)->toBe('private-report-value')
        ->and($detail->toArray())->not->toHaveKeys(['identifying_details', 'serial_fragment', 'staff_notes'])
        ->and($report->toArray())->not->toHaveKey('private_description');
});

test('important foreign keys reject missing campus identities', function () {
    expect(fn () => FoundItem::create([
        'public_reference' => 'ITEM-'.Str::random(10),
        'logged_by_user_id' => 999999999,
        'status' => ItemStatus::DRAFT,
        'title' => 'Invalid employee',
    ]))->toThrow(QueryException::class);

    expect(fn () => LostReport::create([
        'public_reference' => 'REPORT-'.Str::random(10),
        'student_id' => 999999999,
        'status' => ReportStatus::DRAFT,
        'title' => 'Invalid student',
    ]))->toThrow(QueryException::class);
});

test('delete actions restrict operational parents and cascade the owned private extension', function () {
    [$user, $student] = createLostFoundPersistenceActors();
    $category = LostFoundCategory::create(['code' => 'keys']);
    $item = createFoundItemForPersistenceTest($user, $category, 'ITEM-'.Str::random(10));
    $detail = $item->privateDetail()->create(['identifying_details' => 'Owned detail']);

    LostReport::create([
        'public_reference' => 'REPORT-'.Str::random(10),
        'student_id' => $student->id,
        'category_id' => $category->id,
        'resolved_found_item_id' => $item->id,
        'status' => ReportStatus::RESOLVED,
        'title' => 'Resolved report',
    ]);

    expect(fn () => $category->delete())->toThrow(QueryException::class)
        ->and(fn () => $item->delete())->toThrow(QueryException::class);

    DB::table('lost_found_reports')->where('resolved_found_item_id', $item->id)->delete();
    $item->delete();

    expect(DB::table('lost_found_item_private_details')->where('id', $detail->id)->exists())->toBeFalse();
});

test('wave one repository cannot make custody or terminal item states reachable', function () {
    [$user] = createLostFoundPersistenceActors();
    $category = LostFoundCategory::create(['code' => 'bag']);
    $repository = app(FoundItemRepository::class);

    foreach ([ItemStatus::IN_CUSTODY, ItemStatus::RETURNED, ItemStatus::DISPOSED] as $status) {
        expect(fn () => $repository->create([
            'public_reference' => 'ITEM-'.Str::random(10),
            'category_id' => $category->id,
            'logged_by_user_id' => $user->id,
            'status' => $status,
            'title' => 'Unavailable state',
        ]))->toThrow(InvalidArgumentException::class);
    }
});

test('category codes become immutable after operational use', function () {
    [$user] = createLostFoundPersistenceActors();
    $category = LostFoundCategory::create(['code' => 'umbrella']);
    createFoundItemForPersistenceTest($user, $category, 'ITEM-'.Str::random(10));

    expect(fn () => app(LostFoundCategoryRepository::class)->update([
        'code' => 'renamed-umbrella',
    ], $category->id))->toThrow(LogicException::class);
});

test('LostReportRepository rejects generic update of workflow and identity fields', function () {
    [$user, $student] = createLostFoundPersistenceActors();
    $category = LostFoundCategory::create(['code' => 'laptop-'.Str::random(5)]);
    $item = createFoundItemForPersistenceTest($user, $category, 'ITEM-'.Str::random(10));

    $repository = app(LostReportRepository::class);
    $report = $repository->create([
        'public_reference' => 'REPORT-'.Str::random(10),
        'student_id' => $student->id,
        'category_id' => $category->id,
        'status' => ReportStatus::DRAFT,
        'title' => 'Initial Report Title',
        'description' => 'Initial description',
    ]);

    $protectedFieldPayloads = [
        ['status' => ReportStatus::RESOLVED],
        ['resolved_found_item_id' => $item->id],
        ['student_id' => $student->id],
        ['public_reference' => 'REPORT-NEW-REF'],
        ['public_reference_key' => 'report-new-ref'],
        ['resolved_found_item_id' => null],
        ['status' => ReportStatus::DRAFT],
    ];

    foreach ($protectedFieldPayloads as $payload) {
        expect(fn () => $repository->update($payload, $report->id))
            ->toThrow(LogicException::class, 'LostReport workflow and identity fields require a dedicated domain operation.');

        $freshReport = $report->fresh();
        expect($freshReport->status)->toBe(ReportStatus::DRAFT)
            ->and($freshReport->resolved_found_item_id)->toBeNull()
            ->and($freshReport->student_id)->toBe($student->id)
            ->and($freshReport->public_reference)->toBe($report->public_reference);
    }
});

test('LostReportRepository atomically rejects mixed updates containing protected fields', function () {
    [, $student] = createLostFoundPersistenceActors();
    $category = LostFoundCategory::create(['code' => 'book-'.Str::random(5)]);

    $repository = app(LostReportRepository::class);
    $report = $repository->create([
        'public_reference' => 'REPORT-'.Str::random(10),
        'student_id' => $student->id,
        'category_id' => $category->id,
        'status' => ReportStatus::DRAFT,
        'title' => 'Original Title',
    ]);

    expect(fn () => $repository->update([
        'title' => 'Legitimate New Title',
        'status' => ReportStatus::ACTIVE,
    ], $report->id))->toThrow(LogicException::class);

    $fresh = $report->fresh();
    expect($fresh->title)->toBe('Original Title')
        ->and($fresh->status)->toBe(ReportStatus::DRAFT);
});

test('LostReportRepository allows generic update of legitimate editable fields', function () {
    [, $student] = createLostFoundPersistenceActors();
    $category1 = LostFoundCategory::create(['code' => 'card-'.Str::random(5)]);
    $category2 = LostFoundCategory::create(['code' => 'badge-'.Str::random(5)]);

    $repository = app(LostReportRepository::class);
    $report = $repository->create([
        'public_reference' => 'REPORT-'.Str::random(10),
        'student_id' => $student->id,
        'category_id' => $category1->id,
        'status' => ReportStatus::DRAFT,
        'title' => 'Original Title',
        'public_description' => 'Original Public Description',
        'private_description' => 'Original Private Description',
    ]);

    $updated = $repository->update([
        'title' => 'Updated Title',
        'category_id' => $category2->id,
        'public_description' => 'Updated Public Description',
        'private_description' => 'Updated Private Description',
    ], $report->id);

    expect($updated->title)->toBe('Updated Title')
        ->and($updated->category_id)->toBe($category2->id)
        ->and($updated->public_description)->toBe('Updated Public Description')
        ->and($updated->private_description)->toBe('Updated Private Description');
});

test('FoundItemRepository rejects generic update of approved_claim_id and identity fields', function () {
    [$user, $student] = createLostFoundPersistenceActors();
    $category = LostFoundCategory::create(['code' => 'watch-'.Str::random(5)]);
    $repository = app(FoundItemRepository::class);

    $item = $repository->create([
        'public_reference' => 'ITEM-'.Str::random(10),
        'category_id' => $category->id,
        'logged_by_user_id' => $user->id,
        'status' => ItemStatus::REPORTED,
        'title' => 'Initial Item Title',
    ]);

    $protectedFieldPayloads = [
        ['approved_claim_id' => 1],
        ['approved_claim_id' => null],
        ['logged_by_user_id' => $user->id],
        ['public_reference' => 'ITEM-NEW-REF'],
        ['public_reference_key' => 'item-new-ref'],
    ];

    foreach ($protectedFieldPayloads as $payload) {
        expect(fn () => $repository->update($payload, $item->id))
            ->toThrow(LogicException::class, 'FoundItem workflow and identity fields require a dedicated domain operation.');

        $freshItem = $item->fresh();
        expect($freshItem->approved_claim_id)->toBeNull()
            ->and($freshItem->logged_by_user_id)->toBe($user->id)
            ->and($freshItem->public_reference)->toBe($item->public_reference);
    }
});

test('FoundItemRepository atomically rejects mixed updates containing protected fields', function () {
    [$user] = createLostFoundPersistenceActors();
    $category = LostFoundCategory::create(['code' => 'hat-'.Str::random(5)]);
    $repository = app(FoundItemRepository::class);

    $item = $repository->create([
        'public_reference' => 'ITEM-'.Str::random(10),
        'category_id' => $category->id,
        'logged_by_user_id' => $user->id,
        'status' => ItemStatus::REPORTED,
        'title' => 'Original Item Title',
    ]);

    expect(fn () => $repository->update([
        'title' => 'Legitimate Item Title',
        'approved_claim_id' => 1,
    ], $item->id))->toThrow(LogicException::class);

    $fresh = $item->fresh();
    expect($fresh->title)->toBe('Original Item Title')
        ->and($fresh->approved_claim_id)->toBeNull();
});

test('FoundItemRepository allows generic update of legitimate editable fields', function () {
    [$user] = createLostFoundPersistenceActors();
    $category1 = LostFoundCategory::create(['code' => 'coat-'.Str::random(5)]);
    $category2 = LostFoundCategory::create(['code' => 'jacket-'.Str::random(5)]);
    $repository = app(FoundItemRepository::class);

    $item = $repository->create([
        'public_reference' => 'ITEM-'.Str::random(10),
        'category_id' => $category1->id,
        'logged_by_user_id' => $user->id,
        'status' => ItemStatus::DRAFT,
        'title' => 'Original Title',
        'found_location' => 'Original Location',
    ]);

    $updated = $repository->update([
        'title' => 'Updated Item Title',
        'category_id' => $category2->id,
        'found_location' => 'Updated Location',
        'status' => ItemStatus::REPORTED,
    ], $item->id);

    expect($updated->title)->toBe('Updated Item Title')
        ->and($updated->category_id)->toBe($category2->id)
        ->and($updated->found_location)->toBe('Updated Location')
        ->and($updated->status)->toBe(ItemStatus::REPORTED);
});
