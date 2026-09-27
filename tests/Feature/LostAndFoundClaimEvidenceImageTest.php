<?php

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\Flysystem\UnableToCreateDirectory;
use Webkul\LostAndFound\Enums\ClaimStatus;
use Webkul\LostAndFound\Enums\EvidenceType;
use Webkul\LostAndFound\Enums\ItemStatus;
use Webkul\LostAndFound\Models\ClaimEvidence;
use Webkul\LostAndFound\Models\FoundItem;
use Webkul\LostAndFound\Models\Handover;
use Webkul\LostAndFound\Models\LostFoundCategory;
use Webkul\LostAndFound\Models\LostFoundClaim;
use Webkul\LostAndFound\Repositories\LostFoundClaimRepository;
use Webkul\LostAndFound\Services\ClaimEvidenceImageService;
use Webkul\LostAndFound\Services\ClaimEvidenceService;
use Webkul\Student\Models\Student;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

uses(DatabaseTransactions::class);

beforeEach(function () {
    $this->configuredPrivateRoot = config('filesystems.disks.lost_found_private.root');
    $this->step09StorageRoot = sys_get_temp_dir().'/campushub-step09-storage-'.Str::uuid();
    $this->step09PrivateRoot = $this->step09StorageRoot.'/private';
    $this->step09PublicRoot = $this->step09StorageRoot.'/public';
    $this->step09UploadRoot = $this->step09StorageRoot.'/uploads';

    File::makeDirectory($this->step09PrivateRoot, 0700, true);
    File::makeDirectory($this->step09PublicRoot, 0700, true);
    File::makeDirectory($this->step09UploadRoot, 0700, true);

    config()->set('filesystems.disks.lost_found_private.root', $this->step09PrivateRoot);
    config()->set('filesystems.disks.lost_found_private.throw', true);
    config()->set('filesystems.disks.public.root', $this->step09PublicRoot);
    Storage::forgetDisk('lost_found_private');
    Storage::forgetDisk('public');
});

afterEach(function () {
    Storage::forgetDisk('lost_found_private');
    Storage::forgetDisk('public');
    File::deleteDirectory($this->step09StorageRoot);
});

function makeStep09Claim(): array
{
    $role = Role::create([
        'name' => 'Evidence Image Role '.Str::random(8),
        'description' => null,
        'permission_type' => 'all',
        'permissions' => null,
    ]);
    $staff = User::create([
        'name' => 'Evidence Image Staff',
        'email' => Str::random(12).'@example.test',
        'password' => Hash::make(Str::random(32)),
        'status' => true,
        'role_id' => $role->id,
    ]);
    $student = Student::create([
        'university_card_number' => 'EVIDENCE-'.Str::random(12),
        'password' => Str::random(32),
        'name' => 'Evidence Image Student',
    ]);
    $category = LostFoundCategory::create(['code' => 'evidence-'.Str::random(10)]);
    $item = FoundItem::create([
        'public_reference' => 'EVIDENCE-ITEM-'.Str::random(12),
        'category_id' => $category->id,
        'logged_by_user_id' => $staff->id,
        'status' => ItemStatus::REPORTED,
        'title' => 'Evidence image item',
        'found_at' => now(),
        'reported_at' => now(),
    ]);
    $claim = app(LostFoundClaimRepository::class)->create([
        'found_item_id' => $item->id,
        'claimant_student_id' => $student->id,
    ]);

    return [$claim, $item, $student, $staff];
}

function makeStep09Image(string $root, string $format, int $width = 24, int $height = 16, ?string $name = null): UploadedFile
{
    $extension = $format === 'jpeg' ? 'jpg' : $format;
    $mime = $format === 'jpeg' ? 'image/jpeg' : 'image/'.$format;
    $path = $root.'/'.Str::uuid().'.'.$extension;
    $image = imagecreatetruecolor($width, $height);
    $color = imagecolorallocate($image, 37, 99, 173);
    imagefill($image, 0, 0, $color);

    match ($format) {
        'jpeg' => imagejpeg($image, $path, 90),
        'png' => imagepng($image, $path, 6),
        'webp' => imagewebp($image, $path, 90),
    };

    imagedestroy($image);

    return new UploadedFile($path, $name ?? 'ownership-proof.'.$extension, $mime, UPLOAD_ERR_OK, true);
}

function addStep09Exif(UploadedFile $file, int $orientation = 6): void
{
    $tiff = "II\x2A\x00\x08\x00\x00\x00";
    $tiff .= "\x02\x00";
    $tiff .= "\x12\x01\x03\x00\x01\x00\x00\x00".pack('v', $orientation)."\x00\x00";
    $tiff .= "\x25\x88\x04\x00\x01\x00\x00\x00\x26\x00\x00\x00";
    $tiff .= "\x00\x00\x00\x00";
    $tiff .= "\x01\x00\x01\x00\x02\x00\x02\x00\x00\x00N\x00\x00\x00\x00\x00\x00\x00";
    $payload = "Exif\x00\x00".$tiff;
    $jpeg = File::get($file->getPathname());
    File::put(
        $file->getPathname(),
        substr($jpeg, 0, 2)."\xFF\xE1".pack('n', strlen($payload) + 2).$payload.substr($jpeg, 2),
    );
}

function storeStep09Image(LostFoundClaim $claim, UploadedFile $image): ClaimEvidence
{
    return app(ClaimEvidenceImageService::class)->store($claim, $image);
}

test('private disk and schema are focused and prohibited tables remain absent', function () {
    expect($this->configuredPrivateRoot)->toBe(storage_path('app/lost-found-private'))
        ->and(realpath($this->step09PrivateRoot))->not->toBe(realpath($this->step09PublicRoot))
        ->and(str_starts_with(realpath($this->step09PrivateRoot), realpath(storage_path('app/public')) ?: storage_path('app/public')))->toBeFalse()
        ->and(config('filesystems.disks.lost_found_private'))->not->toHaveKeys(['url', 'visibility'])
        ->and(config('filesystems.disks.lost_found_private.throw'))->toBeTrue()
        ->and(Schema::hasColumn('lost_found_claim_evidence', 'storage_key_hash'))->toBeTrue();

    foreach ([
        'lost_found_attachments',
        'lost_found_claim_attachments', 'lost_found_locations', 'lost_found_dispositions',
        'potential_matches',
    ] as $table) {
        expect(Schema::hasTable($table))->toBeFalse();
    }
});

test('valid private evidence images are decoded re-encoded and stored', function (string $format, string $mime) {
    [$claim] = makeStep09Claim();
    $evidence = storeStep09Image($claim, makeStep09Image($this->step09UploadRoot, $format));
    $bytes = Storage::disk('lost_found_private')->get($evidence->file_path);
    $decoded = getimagesizefromstring($bytes);

    expect($evidence->evidence_type)->toBe(EvidenceType::IMAGE_ATTACHMENT)
        ->and($evidence->mime_type)->toBe($mime)
        ->and($evidence->byte_size)->toBe(strlen($bytes))
        ->and($decoded)->toBeArray()
        ->and($decoded['mime'])->toBe($mime)
        ->and(Storage::disk('lost_found_private')->exists($evidence->file_path))->toBeTrue()
        ->and(Storage::disk('public')->allFiles())->toBe([])
        ->and(Storage::disk('lost_found_private')->allFiles('lost-found/staging'))->toBe([]);
})->with([
    'JPEG' => ['jpeg', 'image/jpeg'],
    'PNG' => ['png', 'image/png'],
    'WebP' => ['webp', 'image/webp'],
]);

test('jpeg orientation is normalized and exif and gps metadata are removed', function () {
    [$claim] = makeStep09Claim();
    $upload = makeStep09Image($this->step09UploadRoot, 'jpeg', 12, 8);
    addStep09Exif($upload);
    $sourceExif = exif_read_data($upload->getPathname());

    expect($sourceExif)->toBeArray()
        ->and($sourceExif['Orientation'])->toBe(6)
        ->and($sourceExif['GPSLatitudeRef'])->toBe('N');

    $evidence = storeStep09Image($claim, $upload);
    $storedPath = $this->step09StorageRoot.'/stored.jpg';
    File::put($storedPath, Storage::disk('lost_found_private')->get($evidence->file_path));
    $storedExif = @exif_read_data($storedPath);
    $storedSize = getimagesize($storedPath);

    expect($storedSize[0])->toBe(8)
        ->and($storedSize[1])->toBe(12)
        ->and(is_array($storedExif) ? ($storedExif['Orientation'] ?? null) : null)->toBeNull()
        ->and(is_array($storedExif) ? ($storedExif['GPSLatitudeRef'] ?? null) : null)->toBeNull();
});

test('corrupt active and deferred file formats are rejected without residue', function (string $name, string $mime, string $contents) {
    [$claim] = makeStep09Claim();
    $upload = UploadedFile::fake()->createWithContent($name, $contents);

    expect(fn () => storeStep09Image($claim, $upload))->toThrow(InvalidArgumentException::class);
    expect(ClaimEvidence::query()->count())->toBe(0)
        ->and(Storage::disk('lost_found_private')->allFiles())->toBe([]);
})->with([
    'corrupt JPEG' => ['photo.jpg', 'image/jpeg', 'not-a-jpeg'],
    'SVG' => ['proof.svg', 'image/svg+xml', '<svg xmlns="http://www.w3.org/2000/svg"/>'],
    'PDF' => ['receipt.pdf', 'application/pdf', '%PDF-1.7 invalid evidence'],
]);

test('payload and client extension disagreement is rejected', function () {
    [$claim] = makeStep09Claim();
    $png = makeStep09Image($this->step09UploadRoot, 'png');
    $spoof = new UploadedFile($png->getPathname(), 'ownership.jpg', 'image/jpeg', UPLOAD_ERR_OK, true);

    expect(fn () => storeStep09Image($claim, $spoof))->toThrow(InvalidArgumentException::class);
    expect(ClaimEvidence::query()->count())->toBe(0)
        ->and(Storage::disk('lost_found_private')->allFiles())->toBe([]);
});

test('configured byte boundary is accepted and an exceeded boundary is rejected', function () {
    [$probeClaim] = makeStep09Claim();
    $probe = makeStep09Image($this->step09UploadRoot, 'png');
    $probeEvidence = storeStep09Image($probeClaim, $probe);
    $boundary = max($probe->getSize(), $probeEvidence->byte_size);

    [$claim] = makeStep09Claim();
    $valid = makeStep09Image($this->step09UploadRoot, 'png');
    config()->set('lost_found.claim_evidence_images.max_bytes', $boundary);
    storeStep09Image($claim, $valid);

    [$secondClaim] = makeStep09Claim();
    $tooLarge = makeStep09Image($this->step09UploadRoot, 'png');
    config()->set('lost_found.claim_evidence_images.max_bytes', $boundary - 1);

    expect(fn () => storeStep09Image($secondClaim, $tooLarge))->toThrow(InvalidArgumentException::class);
    expect($secondClaim->evidence()->count())->toBe(0);
});

test('configured dimension and pixel boundaries are enforced before persistence', function () {
    [$claim] = makeStep09Claim();
    $image = makeStep09Image($this->step09UploadRoot, 'png', 30, 20);
    config()->set('lost_found.claim_evidence_images.max_width', 29);

    expect(fn () => storeStep09Image($claim, $image))->toThrow(InvalidArgumentException::class);

    config()->set('lost_found.claim_evidence_images.max_width', 30);
    config()->set('lost_found.claim_evidence_images.max_pixels', 599);

    expect(fn () => storeStep09Image($claim, $image))->toThrow(InvalidArgumentException::class);
    expect($claim->evidence()->count())->toBe(0)
        ->and(Storage::disk('lost_found_private')->allFiles())->toBe([]);
});

test('submitted and needs information claims accept append-only image evidence', function (ClaimStatus $status) {
    [$claim] = makeStep09Claim();
    DB::table('lost_found_claims')->where('id', $claim->id)->update(['status' => $status->value]);

    $evidence = storeStep09Image($claim, makeStep09Image($this->step09UploadRoot, 'jpeg'));

    expect($evidence->claim_id)->toBe($claim->id)
        ->and($claim->evidence()->count())->toBe(1);
})->with([
    'submitted' => ClaimStatus::SUBMITTED,
    'needs information' => ClaimStatus::NEEDS_INFORMATION,
]);

test('non appendable claim states reject image evidence', function (ClaimStatus $status) {
    [$claim] = makeStep09Claim();
    DB::table('lost_found_claims')->where('id', $claim->id)->update(['status' => $status->value]);

    expect(fn () => storeStep09Image($claim, makeStep09Image($this->step09UploadRoot, 'jpeg')))
        ->toThrow(DomainException::class);
    expect($claim->evidence()->count())->toBe(0)
        ->and(Storage::disk('lost_found_private')->allFiles())->toBe([]);
})->with([
    'under review' => ClaimStatus::UNDER_REVIEW,
    'approved' => ClaimStatus::APPROVED,
    'rejected' => ClaimStatus::REJECTED,
    'withdrawn' => ClaimStatus::WITHDRAWN,
]);

test('a stale submitted model is reloaded and rejected after a claim state race', function () {
    [$claim] = makeStep09Claim();
    expect($claim->status)->toBe(ClaimStatus::SUBMITTED);
    DB::table('lost_found_claims')->where('id', $claim->id)->update([
        'status' => ClaimStatus::UNDER_REVIEW->value,
    ]);

    expect(fn () => storeStep09Image($claim, makeStep09Image($this->step09UploadRoot, 'png')))
        ->toThrow(DomainException::class);
    expect($claim->evidence()->count())->toBe(0)
        ->and(Storage::disk('lost_found_private')->allFiles())->toBe([]);
});

test('post handover claims reject evidence and preserve final ownership', function () {
    [$claim, $item, $student, $staff] = makeStep09Claim();
    DB::table('lost_found_claims')->where('id', $claim->id)->update(['status' => ClaimStatus::APPROVED->value]);
    DB::table('lost_found_items')->where('id', $item->id)->update([
        'approved_claim_id' => $claim->id,
        'status' => ItemStatus::RETURNED->value,
    ]);
    Handover::create([
        'found_item_id' => $item->id,
        'claim_id' => $claim->id,
        'recipient_student_id' => $student->id,
        'staff_user_id' => $staff->id,
        'verification_method' => 'student card visual check',
        'handed_over_at' => now(),
    ]);

    expect(fn () => storeStep09Image($claim, makeStep09Image($this->step09UploadRoot, 'jpeg')))
        ->toThrow(DomainException::class);
    expect($claim->evidence()->count())->toBe(0)
        ->and($item->refresh()->approved_claim_id)->toBe($claim->id)
        ->and(Storage::disk('lost_found_private')->allFiles())->toBe([]);
});

test('claim evidence is immutable while non file evidence remains supported', function () {
    [$claim] = makeStep09Claim();
    $imageEvidence = storeStep09Image($claim, makeStep09Image($this->step09UploadRoot, 'jpeg'));
    $textEvidence = app(ClaimEvidenceService::class)->addTextEvidence(
        $claim,
        EvidenceType::TEXT_DESCRIPTION,
        'A scratch under the case identifies the item.',
    );

    $imageEvidence->mime_type = 'image/png';
    expect(fn () => $imageEvidence->save())->toThrow(LogicException::class);
    expect(fn () => ClaimEvidence::findOrFail($imageEvidence->id)->delete())->toThrow(LogicException::class);
    expect($textEvidence->text_value)->toBe('A scratch under the case identifies the item.')
        ->and($textEvidence->file_path)->toBeNull()
        ->and($textEvidence->storage_key_hash)->toBeNull()
        ->and($textEvidence->mime_type)->toBeNull()
        ->and($textEvidence->byte_size)->toBeNull();
});

test('file and non file metadata consistency is enforced', function () {
    [$claim] = makeStep09Claim();

    expect(fn () => $claim->evidence()->create([
        'evidence_type' => EvidenceType::IMAGE_ATTACHMENT,
        'file_path' => 'incomplete.jpg',
        'submitted_at' => now(),
    ]))->toThrow(InvalidArgumentException::class);

    expect(fn () => $claim->evidence()->create([
        'evidence_type' => EvidenceType::TEXT_DESCRIPTION,
        'text_value' => 'Text with false file metadata.',
        'file_path' => 'forbidden.jpg',
        'submitted_at' => now(),
    ]))->toThrow(InvalidArgumentException::class);

    expect(fn () => app(ClaimEvidenceService::class)->addTextEvidence(
        $claim,
        EvidenceType::IMAGE_ATTACHMENT,
        'Not an image.',
    ))->toThrow(InvalidArgumentException::class);
});

test('keys are opaque unique hashed once encrypted and hidden from serialization', function () {
    [$claim] = makeStep09Claim();
    $first = storeStep09Image(
        $claim,
        makeStep09Image($this->step09UploadRoot, 'png', name: '../../private ownership.png'),
    );
    $second = storeStep09Image($claim, makeStep09Image($this->step09UploadRoot, 'png'));
    $raw = DB::table('lost_found_claim_evidence')->find($first->id);

    expect($first->file_path)->not->toBe($second->file_path)
        ->and($first->file_path)->toMatch('#^lost-found/claims/[0-9a-f-]{36}/[0-9a-f-]{36}\.png$#')
        ->and($first->file_path)->not->toContain('private ownership')
        ->and($first->storage_key_hash)->toBe(hash('sha256', $first->file_path))
        ->and($first->original_name)->toBe('private ownership.png')
        ->and($raw->file_path)->not->toBe($first->file_path)
        ->and($raw->original_name)->not->toBe($first->original_name)
        ->and($first->toArray())->not->toHaveKeys([
            'file_path', 'storage_key_hash', 'original_name', 'mime_type', 'byte_size',
        ])
        ->and(config('filesystems.disks.lost_found_private'))->not->toHaveKey('url');

    expect(fn () => DB::table('lost_found_claim_evidence')->insert([
        'claim_id' => $claim->id,
        'evidence_type' => EvidenceType::IMAGE_ATTACHMENT->value,
        'file_path' => 'not-plaintext-duplicate',
        'storage_key_hash' => $first->storage_key_hash,
        'original_name' => null,
        'mime_type' => 'image/png',
        'byte_size' => 1,
        'submitted_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);
});

test('database failure after finalization removes final and staging files', function () {
    [$claim] = makeStep09Claim();
    DB::unprepared("CREATE TRIGGER step09_evidence_failure BEFORE INSERT ON lost_found_claim_evidence WHEN NEW.evidence_type = 'image_attachment' BEGIN SELECT RAISE(ABORT, 'forced evidence failure'); END");

    try {
        expect(fn () => storeStep09Image($claim, makeStep09Image($this->step09UploadRoot, 'jpeg')))
            ->toThrow(QueryException::class);
    } finally {
        DB::unprepared('DROP TRIGGER IF EXISTS step09_evidence_failure');
    }

    expect($claim->evidence()->count())->toBe(0)
        ->and(Storage::disk('lost_found_private')->allFiles())->toBe([]);
});

test('private storage failure cannot commit false evidence metadata', function () {
    [$claim] = makeStep09Claim();
    File::deleteDirectory($this->step09PrivateRoot);
    File::put($this->step09PrivateRoot, 'not a directory');
    Storage::forgetDisk('lost_found_private');

    expect(fn () => storeStep09Image($claim, makeStep09Image($this->step09UploadRoot, 'webp')))
        ->toThrow(UnableToCreateDirectory::class);
    expect($claim->evidence()->count())->toBe(0);
});

test('an invalid claim cannot produce evidence or private files', function () {
    $claim = new LostFoundClaim;

    expect(fn () => storeStep09Image($claim, makeStep09Image($this->step09UploadRoot, 'jpeg')))
        ->toThrow(InvalidArgumentException::class);
    expect(ClaimEvidence::query()->count())->toBe(0)
        ->and(Storage::disk('lost_found_private')->allFiles())->toBe([]);
});

test('the claim foreign key rejects orphan evidence metadata', function () {
    expect(fn () => DB::table('lost_found_claim_evidence')->insert([
        'claim_id' => PHP_INT_MAX,
        'evidence_type' => EvidenceType::IMAGE_ATTACHMENT->value,
        'file_path' => 'encrypted-value-not-used-by-the-service',
        'storage_key_hash' => str_repeat('a', 64),
        'original_name' => null,
        'mime_type' => 'image/jpeg',
        'byte_size' => 1,
        'submitted_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);

    expect(ClaimEvidence::query()->count())->toBe(0)
        ->and(Storage::disk('lost_found_private')->allFiles())->toBe([]);
});
