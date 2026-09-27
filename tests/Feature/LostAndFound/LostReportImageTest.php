<?php

namespace Tests\Feature\LostAndFound;

use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;
use Tests\TestCase;
use Webkul\LostAndFound\Enums\ItemStatus;
use Webkul\LostAndFound\Enums\ReportStatus;
use Webkul\LostAndFound\Models\FoundItem;
use Webkul\LostAndFound\Models\LostReport;
use Webkul\LostAndFound\Models\LostReportImage;
use Webkul\LostAndFound\Services\LostReportImageService;
use Webkul\Student\Models\Student;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

class LostReportImageTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('lost_found_private');
    }

    private function createDummyStudent(): Student
    {
        return Student::create([
            'university_card_number' => 'STU-'.Str::random(10),
            'password' => Hash::make('password'),
            'name' => 'Report Student',
        ]);
    }

    private function createDummyReport(Student $student, ReportStatus $status = ReportStatus::ACTIVE): LostReport
    {
        return LostReport::create([
            'public_reference' => 'LR-2026-'.rand(100000, 999999),
            'student_id' => $student->id,
            'status' => $status,
            'title' => 'Lost Black Wallet',
            'lost_location' => 'Student Union',
            'lost_at' => now(),
            'submitted_at' => now(),
        ]);
    }

    private function createSampleJpeg(): UploadedFile
    {
        $im = imagecreatetruecolor(100, 100);
        $red = imagecolorallocate($im, 255, 0, 0);
        imagefill($im, 0, 0, $red);

        ob_start();
        imagejpeg($im, null, 90);
        $bytes = ob_get_clean();
        imagedestroy($im);

        return UploadedFile::fake()->createWithContent('report_item.jpg', $bytes);
    }

    private function createSamplePng(): UploadedFile
    {
        $im = imagecreatetruecolor(100, 100);
        $blue = imagecolorallocate($im, 0, 0, 255);
        imagefill($im, 0, 0, $blue);

        ob_start();
        imagepng($im, null, 6);
        $bytes = ob_get_clean();
        imagedestroy($im);

        return UploadedFile::fake()->createWithContent('report_item.png', $bytes);
    }

    private function createSampleWebp(): UploadedFile
    {
        $im = imagecreatetruecolor(100, 100);
        $green = imagecolorallocate($im, 0, 255, 0);
        imagefill($im, 0, 0, $green);

        ob_start();
        imagewebp($im, null, 90);
        $bytes = ob_get_clean();
        imagedestroy($im);

        return UploadedFile::fake()->createWithContent('report_item.webp', $bytes);
    }

    public function test_schema_and_columns_exist(): void
    {
        $this->assertTrue(Schema::hasTable('lost_found_report_images'));
        $this->assertTrue(Schema::hasColumns('lost_found_report_images', [
            'id',
            'lost_report_id',
            'storage_key',
            'mime_type',
            'byte_size',
            'sort_order',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_no_visibility_and_no_original_filename_column(): void
    {
        $this->assertFalse(Schema::hasColumn('lost_found_report_images', 'visibility'));
        $this->assertFalse(Schema::hasColumn('lost_found_report_images', 'is_public'));
        $this->assertFalse(Schema::hasColumn('lost_found_report_images', 'original_name'));
        $this->assertFalse(Schema::hasColumn('lost_found_report_images', 'original_filename'));
    }

    public function test_unique_storage_key(): void
    {
        $student = $this->createDummyStudent();
        $report = $this->createDummyReport($student);

        LostReportImage::create([
            'lost_report_id' => $report->id,
            'storage_key' => 'unique_report_key_123',
            'mime_type' => 'image/jpeg',
            'byte_size' => 1024,
            'sort_order' => 0,
        ]);

        $this->expectException(QueryException::class);

        LostReportImage::create([
            'lost_report_id' => $report->id,
            'storage_key' => 'unique_report_key_123',
            'mime_type' => 'image/jpeg',
            'byte_size' => 1024,
            'sort_order' => 0,
        ]);
    }

    public function test_private_jpeg_upload(): void
    {
        $student = $this->createDummyStudent();
        $report = $this->createDummyReport($student);
        $service = new LostReportImageService;

        $image = $service->addImage(
            $report,
            $this->createSampleJpeg(),
        );

        $this->assertEquals('image/jpeg', $image->mime_type);
        $this->assertGreaterThan(0, $image->byte_size);

        Storage::disk('lost_found_private')->assertExists($image->storage_key);
        Storage::disk('public')->assertMissing($image->storage_key);
    }

    public function test_private_png_upload(): void
    {
        $student = $this->createDummyStudent();
        $report = $this->createDummyReport($student);
        $service = new LostReportImageService;

        $image = $service->addImage(
            $report,
            $this->createSamplePng(),
        );

        $this->assertEquals('image/png', $image->mime_type);

        Storage::disk('lost_found_private')->assertExists($image->storage_key);
        Storage::disk('public')->assertMissing($image->storage_key);
    }

    public function test_private_webp_upload(): void
    {
        $student = $this->createDummyStudent();
        $report = $this->createDummyReport($student);
        $service = new LostReportImageService;

        $image = $service->addImage(
            $report,
            $this->createSampleWebp(),
        );

        $this->assertEquals('image/webp', $image->mime_type);

        Storage::disk('lost_found_private')->assertExists($image->storage_key);
        Storage::disk('public')->assertMissing($image->storage_key);
    }

    public function test_invalid_formats_rejected(): void
    {
        $student = $this->createDummyStudent();
        $report = $this->createDummyReport($student);
        $service = new LostReportImageService;

        $svgFile = UploadedFile::fake()->createWithContent('icon.svg', '<svg></svg>');

        $this->expectException(InvalidArgumentException::class);
        $service->addImage($report, $svgFile);
    }

    public function test_pdf_rejected(): void
    {
        $student = $this->createDummyStudent();
        $report = $this->createDummyReport($student);
        $service = new LostReportImageService;

        $pdfFile = UploadedFile::fake()->createWithContent('doc.pdf', '%PDF-1.4');

        $this->expectException(InvalidArgumentException::class);
        $service->addImage($report, $pdfFile);
    }

    public function test_opaque_random_storage_key(): void
    {
        $student = $this->createDummyStudent();
        $report = $this->createDummyReport($student);
        $service = new LostReportImageService;

        $image = $service->addImage(
            $report,
            $this->createSampleJpeg(),
        );

        $this->assertMatchesRegularExpression('#^lost-found/reports/[0-9a-f-]{36}/[0-9a-f-]{36}\.jpg$#', $image->storage_key);
        $this->assertStringNotContainsString('report_item', $image->storage_key);
    }

    public function test_sort_order_and_ordering(): void
    {
        $student = $this->createDummyStudent();
        $report = $this->createDummyReport($student);
        $service = new LostReportImageService;

        $img2 = $service->addImage($report, $this->createSampleJpeg(), 10);
        $img1 = $service->addImage($report, $this->createSampleJpeg(), 5);
        $img0 = $service->addImage($report, $this->createSampleJpeg(), 0);

        $allImages = $report->images;
        $this->assertCount(3, $allImages);
        $this->assertEquals($img0->id, $allImages[0]->id);
        $this->assertEquals($img1->id, $allImages[1]->id);
        $this->assertEquals($img2->id, $allImages[2]->id);
    }

    public function test_allowed_report_states(): void
    {
        $student = $this->createDummyStudent();
        $service = new LostReportImageService;

        $draftReport = $this->createDummyReport($student, ReportStatus::DRAFT);
        $imgDraft = $service->addImage($draftReport, $this->createSampleJpeg());
        $this->assertNotNull($imgDraft->id);

        $activeReport = $this->createDummyReport($student, ReportStatus::ACTIVE);
        $imgActive = $service->addImage($activeReport, $this->createSampleJpeg());
        $this->assertNotNull($imgActive->id);
    }

    public function test_disallowed_resolved_status(): void
    {
        $student = $this->createDummyStudent();
        $report = $this->createDummyReport($student, ReportStatus::RESOLVED);
        $service = new LostReportImageService;

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('LostReport images cannot be added in current status [resolved].');

        $service->addImage($report, $this->createSampleJpeg());
    }

    public function test_disallowed_cancelled_status(): void
    {
        $student = $this->createDummyStudent();
        $report = $this->createDummyReport($student, ReportStatus::CANCELLED);
        $service = new LostReportImageService;

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('LostReport images cannot be added in current status [cancelled].');

        $service->addImage($report, $this->createSampleJpeg());
    }

    public function test_confirmed_resolution_restriction(): void
    {
        $student = $this->createDummyStudent();
        $role = Role::create([
            'name' => 'Role '.Str::random(8),
            'description' => null,
            'permission_type' => 'all',
            'permissions' => null,
        ]);
        $user = User::create([
            'name' => 'Staff User',
            'email' => Str::random(12).'@example.test',
            'password' => Hash::make(Str::random(32)),
            'status' => true,
            'role_id' => $role->id,
        ]);
        $foundItem = FoundItem::create([
            'public_reference' => 'LF-ITEM-'.rand(100000, 999999),
            'logged_by_user_id' => $user->id,
            'status' => ItemStatus::RETURNED,
            'title' => 'Resolved Item',
            'found_location' => 'Library',
            'found_at' => now(),
        ]);

        $report = $this->createDummyReport($student, ReportStatus::ACTIVE);
        $report->update(['resolved_found_item_id' => $foundItem->id]);

        $service = new LostReportImageService;

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('LostReport images cannot be mutated after a confirmed resolution.');

        $service->addImage($report, $this->createSampleJpeg());
    }

    public function test_student_ownership_resolution(): void
    {
        $student = $this->createDummyStudent();
        $report = $this->createDummyReport($student);
        $service = new LostReportImageService;

        $image = $service->addImage($report, $this->createSampleJpeg());

        $this->assertEquals($student->id, $image->report->student_id);
    }

    public function test_append_only_immutability(): void
    {
        $student = $this->createDummyStudent();
        $report = $this->createDummyReport($student);
        $service = new LostReportImageService;

        $image = $service->addImage($report, $this->createSampleJpeg());

        $this->expectException(LogicException::class);
        $image->update(['sort_order' => 999]);
    }

    public function test_serialization_protection(): void
    {
        $student = $this->createDummyStudent();
        $report = $this->createDummyReport($student);
        $service = new LostReportImageService;

        $image = $service->addImage($report, $this->createSampleJpeg());

        $array = $image->toArray();
        $json = json_encode($image);

        $this->assertArrayNotHasKey('storage_key', $array);
        $this->assertStringNotContainsString('storage_key', $json);
    }

    public function test_no_public_storage_used(): void
    {
        $student = $this->createDummyStudent();
        $report = $this->createDummyReport($student);
        $service = new LostReportImageService;

        $image = $service->addImage($report, $this->createSampleJpeg());

        Storage::disk('public')->assertMissing($image->storage_key);
        $this->assertCount(0, Storage::disk('public')->allFiles());
    }

    public function test_db_failure_compensation(): void
    {
        $student = $this->createDummyStudent();
        $report = $this->createDummyReport($student);
        $service = new LostReportImageService;

        DB::unprepared("CREATE TRIGGER step11_report_image_failure BEFORE INSERT ON lost_found_report_images BEGIN SELECT RAISE(ABORT, 'forced report image failure'); END");

        try {
            $this->expectException(QueryException::class);
            $service->addImage($report, $this->createSampleJpeg());
        } finally {
            DB::unprepared('DROP TRIGGER IF EXISTS step11_report_image_failure');
        }

        $this->assertEquals(0, LostReportImage::count());
        $this->assertCount(0, Storage::disk('lost_found_private')->allFiles('lost-found/reports'));
    }
}
