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
use Tests\TestCase;
use Webkul\LostAndFound\Enums\FoundItemImageVisibility;
use Webkul\LostAndFound\Enums\ItemStatus;
use Webkul\LostAndFound\Models\FoundItem;
use Webkul\LostAndFound\Models\FoundItemImage;
use Webkul\LostAndFound\Repositories\LostFoundClaimRepository;
use Webkul\LostAndFound\Services\FoundItemImageService;
use Webkul\Student\Models\Student;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

class FoundItemImageTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('lost_found_private');
    }

    private function createDummyUser(): User
    {
        $role = Role::create([
            'name' => 'Role '.Str::random(8),
            'description' => null,
            'permission_type' => 'all',
            'permissions' => null,
        ]);

        return User::create([
            'name' => 'Test Employee',
            'email' => 'employee-'.uniqid().'@example.com',
            'password' => Hash::make('password'),
            'role_id' => $role->id,
            'status' => 1,
        ]);
    }

    private function createDummyStudent(): Student
    {
        return Student::create([
            'university_card_number' => 'STU-'.Str::random(10),
            'password' => Hash::make('password'),
            'name' => 'Test Student',
        ]);
    }

    private function createDummyItem(User $user, ItemStatus $status = ItemStatus::IN_CUSTODY): FoundItem
    {
        return FoundItem::create([
            'public_reference' => 'LF-2026-'.rand(100000, 999999),
            'logged_by_user_id' => $user->id,
            'status' => $status,
            'title' => 'Test Found Laptop',
            'found_location' => 'Main Library',
            'found_at' => now(),
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

        return UploadedFile::fake()->createWithContent('test_item.jpg', $bytes);
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

        return UploadedFile::fake()->createWithContent('test_item.png', $bytes);
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

        return UploadedFile::fake()->createWithContent('test_item.webp', $bytes);
    }

    public function test_schema_and_columns_exist(): void
    {
        $this->assertTrue(Schema::hasTable('lost_found_item_images'));
        $this->assertTrue(Schema::hasColumns('lost_found_item_images', [
            'id',
            'found_item_id',
            'created_by_user_id',
            'visibility',
            'storage_key',
            'mime_type',
            'byte_size',
            'sort_order',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_original_filename_column_absent(): void
    {
        $this->assertFalse(Schema::hasColumn('lost_found_item_images', 'original_name'));
        $this->assertFalse(Schema::hasColumn('lost_found_item_images', 'original_filename'));
    }

    public function test_unique_storage_key(): void
    {
        $user = $this->createDummyUser();
        $item = $this->createDummyItem($user);

        FoundItemImage::create([
            'found_item_id' => $item->id,
            'created_by_user_id' => $user->id,
            'visibility' => FoundItemImageVisibility::PUBLIC_SAFE,
            'storage_key' => 'unique_key_123',
            'mime_type' => 'image/jpeg',
            'byte_size' => 1024,
            'sort_order' => 0,
        ]);

        $this->expectException(QueryException::class);

        FoundItemImage::create([
            'found_item_id' => $item->id,
            'created_by_user_id' => $user->id,
            'visibility' => FoundItemImageVisibility::PUBLIC_SAFE,
            'storage_key' => 'unique_key_123',
            'mime_type' => 'image/jpeg',
            'byte_size' => 1024,
            'sort_order' => 0,
        ]);
    }

    public function test_public_safe_jpeg_upload(): void
    {
        $user = $this->createDummyUser();
        $item = $this->createDummyItem($user);
        $service = new FoundItemImageService;

        $image = $service->addImage(
            $item,
            $user,
            FoundItemImageVisibility::PUBLIC_SAFE,
            $this->createSampleJpeg(),
        );

        $this->assertEquals(FoundItemImageVisibility::PUBLIC_SAFE, $image->visibility);
        $this->assertEquals('image/jpeg', $image->mime_type);
        $this->assertGreaterThan(0, $image->byte_size);

        Storage::disk('public')->assertExists($image->storage_key);
        Storage::disk('lost_found_private')->assertMissing($image->storage_key);
    }

    public function test_public_safe_png_upload(): void
    {
        $user = $this->createDummyUser();
        $item = $this->createDummyItem($user);
        $service = new FoundItemImageService;

        $image = $service->addImage(
            $item,
            $user,
            FoundItemImageVisibility::PUBLIC_SAFE,
            $this->createSamplePng(),
        );

        $this->assertEquals(FoundItemImageVisibility::PUBLIC_SAFE, $image->visibility);
        $this->assertEquals('image/png', $image->mime_type);

        Storage::disk('public')->assertExists($image->storage_key);
        Storage::disk('lost_found_private')->assertMissing($image->storage_key);
    }

    public function test_public_safe_webp_upload(): void
    {
        $user = $this->createDummyUser();
        $item = $this->createDummyItem($user);
        $service = new FoundItemImageService;

        $image = $service->addImage(
            $item,
            $user,
            FoundItemImageVisibility::PUBLIC_SAFE,
            $this->createSampleWebp(),
        );

        $this->assertEquals(FoundItemImageVisibility::PUBLIC_SAFE, $image->visibility);
        $this->assertEquals('image/webp', $image->mime_type);

        Storage::disk('public')->assertExists($image->storage_key);
        Storage::disk('lost_found_private')->assertMissing($image->storage_key);
    }

    public function test_staff_only_jpeg_upload(): void
    {
        $user = $this->createDummyUser();
        $item = $this->createDummyItem($user);
        $service = new FoundItemImageService;

        $image = $service->addImage(
            $item,
            $user,
            FoundItemImageVisibility::STAFF_ONLY,
            $this->createSampleJpeg(),
        );

        $this->assertEquals(FoundItemImageVisibility::STAFF_ONLY, $image->visibility);
        $this->assertEquals('image/jpeg', $image->mime_type);

        Storage::disk('lost_found_private')->assertExists($image->storage_key);
        Storage::disk('public')->assertMissing($image->storage_key);
    }

    public function test_invalid_formats_rejected(): void
    {
        $user = $this->createDummyUser();
        $item = $this->createDummyItem($user);
        $service = new FoundItemImageService;

        $svgFile = UploadedFile::fake()->createWithContent('icon.svg', '<svg></svg>');

        $this->expectException(InvalidArgumentException::class);
        $service->addImage($item, $user, FoundItemImageVisibility::PUBLIC_SAFE, $svgFile);
    }

    public function test_pdf_rejected(): void
    {
        $user = $this->createDummyUser();
        $item = $this->createDummyItem($user);
        $service = new FoundItemImageService;

        $pdfFile = UploadedFile::fake()->createWithContent('doc.pdf', '%PDF-1.4');

        $this->expectException(InvalidArgumentException::class);
        $service->addImage($item, $user, FoundItemImageVisibility::PUBLIC_SAFE, $pdfFile);
    }

    public function test_opaque_random_storage_key(): void
    {
        $user = $this->createDummyUser();
        $item = $this->createDummyItem($user);
        $service = new FoundItemImageService;

        $image = $service->addImage(
            $item,
            $user,
            FoundItemImageVisibility::PUBLIC_SAFE,
            $this->createSampleJpeg(),
        );

        $this->assertMatchesRegularExpression('#^lost-found/items/public/[0-9a-f-]{36}/[0-9a-f-]{36}\.jpg$#', $image->storage_key);
        $this->assertStringNotContainsString('test_item', $image->storage_key);
    }

    public function test_sort_order_and_cover_image_selection(): void
    {
        $user = $this->createDummyUser();
        $item = $this->createDummyItem($user);
        $service = new FoundItemImageService;

        $imgStaff = $service->addImage($item, $user, FoundItemImageVisibility::STAFF_ONLY, $this->createSampleJpeg(), 0);
        $imgPub2 = $service->addImage($item, $user, FoundItemImageVisibility::PUBLIC_SAFE, $this->createSampleJpeg(), 10);
        $imgPub1 = $service->addImage($item, $user, FoundItemImageVisibility::PUBLIC_SAFE, $this->createSampleJpeg(), 5);

        $cover = $item->coverImage;

        $this->assertNotNull($cover);
        $this->assertEquals($imgPub1->id, $cover->id);
        $this->assertNotEquals($imgStaff->id, $cover->id);

        $allImages = $item->images;
        $this->assertCount(3, $allImages);
        $this->assertEquals($imgStaff->id, $allImages[0]->id);
        $this->assertEquals($imgPub1->id, $allImages[1]->id);
        $this->assertEquals($imgPub2->id, $allImages[2]->id);
    }

    public function test_claim_freeze_invariant(): void
    {
        $user = $this->createDummyUser();
        $student = $this->createDummyStudent();
        $item = $this->createDummyItem($user);
        $service = new FoundItemImageService;

        app(LostFoundClaimRepository::class)->create([
            'found_item_id' => $item->id,
            'claimant_student_id' => $student->id,
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('FoundItem images cannot be mutated after a claim exists.');

        $service->addImage($item, $user, FoundItemImageVisibility::PUBLIC_SAFE, $this->createSampleJpeg());
    }

    public function test_terminal_item_status_restriction(): void
    {
        $user = $this->createDummyUser();
        $item = $this->createDummyItem($user, ItemStatus::RETURNED);
        $service = new FoundItemImageService;

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('FoundItem images cannot be added in terminal status [returned].');

        $service->addImage($item, $user, FoundItemImageVisibility::PUBLIC_SAFE, $this->createSampleJpeg());
    }

    public function test_serialization_protection(): void
    {
        $user = $this->createDummyUser();
        $item = $this->createDummyItem($user);
        $service = new FoundItemImageService;

        $image = $service->addImage($item, $user, FoundItemImageVisibility::PUBLIC_SAFE, $this->createSampleJpeg());

        $array = $image->toArray();
        $json = json_encode($image);

        $this->assertArrayNotHasKey('storage_key', $array);
        $this->assertStringNotContainsString('storage_key', $json);
    }

    public function test_public_db_failure_compensation(): void
    {
        $user = $this->createDummyUser();
        $item = $this->createDummyItem($user);

        $service = new FoundItemImageService;

        try {
            DB::transaction(function () use ($service, $item, $user) {
                $service->addImage($item, $user, FoundItemImageVisibility::PUBLIC_SAFE, $this->createSampleJpeg());
                throw new \Exception('Simulated DB failure post-finalization');
            });
        } catch (\Exception $e) {
            $this->assertEquals('Simulated DB failure post-finalization', $e->getMessage());
        }

        $this->assertEquals(0, FoundItemImage::count());
    }
}
