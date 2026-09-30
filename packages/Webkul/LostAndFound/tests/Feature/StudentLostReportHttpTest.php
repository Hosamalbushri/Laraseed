<?php

namespace Tests\Feature\LostAndFound;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;
use Webkul\LostAndFound\Enums\ReportStatus;
use Webkul\LostAndFound\Models\LostFoundCategory;
use Webkul\LostAndFound\Models\LostReport;
use Webkul\LostAndFound\Services\Application\StudentReportApplicationService;
use Webkul\Student\Models\Student;
use Webkul\User\Models\User;

class StudentLostReportHttpTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('lost_found_private');
        Storage::fake('public');
    }

    protected function createStudent(): Student
    {
        return Student::create([
            'university_card_number' => 'STU-'.Str::random(8),
            'name' => 'Student '.Str::random(6),
            'password' => Hash::make('password'),
        ]);
    }

    protected function createCategory(): LostFoundCategory
    {
        return LostFoundCategory::create([
            'code' => 'cat-'.Str::random(6),
            'sort_order' => 1,
            'is_active' => true,
        ]);
    }

    protected function createUser(): User
    {
        return User::create([
            'name' => 'Employee '.Str::random(6),
            'email' => 'employee-'.Str::random(6).'@example.com',
            'password' => Hash::make('password'),
            'role_id' => 1,
            'status' => 1,
        ]);
    }

    public function test_authenticated_student_can_create_lost_report_via_http(): void
    {
        $student = $this->createStudent();
        $category = $this->createCategory();

        $response = $this->actingAs($student, 'student')
            ->postJson(route('student.lost_found.reports.store'), [
                'category_id' => $category->id,
                'title' => 'Lost Water Bottle',
                'description' => 'Blue metal bottle left in Library',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'Lost Water Bottle')
            ->assertJsonPath('data.status', ReportStatus::DRAFT->value);

        $reportId = $response->json('data.id');
        $this->assertDatabaseHas('lost_found_reports', [
            'id' => $reportId,
            'student_id' => $student->id,
            'title' => 'Lost Water Bottle',
        ]);
    }

    public function test_unauthenticated_json_request_returns_401(): void
    {
        $category = $this->createCategory();

        $response = $this->postJson(route('student.lost_found.reports.store'), [
            'category_id' => $category->id,
            'title' => 'Unauthenticated Report',
        ]);

        $response->assertStatus(401);
    }

    public function test_unauthenticated_html_request_redirects(): void
    {
        $category = $this->createCategory();

        $response = $this->post(route('student.lost_found.reports.store'), [
            'category_id' => $category->id,
            'title' => 'Unauthenticated Report',
        ]);

        $response->assertStatus(302);
    }

    public function test_wrong_guard_user_cannot_access_student_lost_report_endpoints(): void
    {
        $user = $this->createUser();
        $category = $this->createCategory();

        $response = $this->actingAs($user, 'user')
            ->postJson(route('student.lost_found.reports.store'), [
                'category_id' => $category->id,
                'title' => 'User Guard Attempt',
            ]);

        $response->assertStatus(401);
    }

    public function test_student_id_spoofing_in_payload_is_ignored(): void
    {
        $student1 = $this->createStudent();
        $student2 = $this->createStudent();
        $category = $this->createCategory();

        $response = $this->actingAs($student1, 'student')
            ->postJson(route('student.lost_found.reports.store'), [
                'student_id' => $student2->id,
                'category_id' => $category->id,
                'title' => 'Spoof Attempt Report',
            ]);

        $response->assertStatus(201);
        $reportId = $response->json('data.id');

        $report = LostReport::findOrFail($reportId);
        $this->assertEquals($student1->id, $report->student_id);
    }

    public function test_workflow_fields_in_payload_are_ignored(): void
    {
        $student = $this->createStudent();
        $category = $this->createCategory();

        $response = $this->actingAs($student, 'student')
            ->postJson(route('student.lost_found.reports.store'), [
                'category_id' => $category->id,
                'title' => 'Workflow Injection Report',
                'status' => ReportStatus::RESOLVED->value,
                'resolved_found_item_id' => 9999,
                'public_reference' => 'FORGED-REF-123',
                'public_reference_key' => 'forged-ref-123',
            ]);

        $response->assertStatus(201);
        $reportId = $response->json('data.id');

        $report = LostReport::findOrFail($reportId);
        $this->assertEquals(ReportStatus::DRAFT, $report->status);
        $this->assertNull($report->resolved_found_item_id);
        $this->assertNotEquals('FORGED-REF-123', $report->public_reference);
    }

    public function test_authenticated_student_can_update_own_lost_report_via_http(): void
    {
        $student = $this->createStudent();
        $category = $this->createCategory();

        $reportAppService = app(StudentReportApplicationService::class);
        $report = $reportAppService->createLostReport($student, [
            'category_id' => $category->id,
            'title' => 'Original Title',
        ]);

        $response = $this->actingAs($student, 'student')
            ->putJson(route('student.lost_found.reports.update', $report->id), [
                'title' => 'Updated Title via HTTP',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Updated Title via HTTP');

        $this->assertEquals('Updated Title via HTTP', $report->fresh()->title);
    }

    public function test_cross_student_update_returns_404_without_db_changes(): void
    {
        $student1 = $this->createStudent();
        $student2 = $this->createStudent();
        $category = $this->createCategory();

        $reportAppService = app(StudentReportApplicationService::class);
        $report1 = $reportAppService->createLostReport($student1, [
            'category_id' => $category->id,
            'title' => 'Student 1 Report',
        ]);

        $response = $this->actingAs($student2, 'student')
            ->putJson(route('student.lost_found.reports.update', $report1->id), [
                'title' => 'Hacked Title',
            ]);

        $response->assertStatus(404);
        $this->assertEquals('Student 1 Report', $report1->fresh()->title);
    }

    public function test_authenticated_student_can_upload_image_to_own_lost_report_via_http(): void
    {
        $student = $this->createStudent();
        $category = $this->createCategory();

        $reportAppService = app(StudentReportApplicationService::class);
        $report = $reportAppService->createLostReport($student, [
            'category_id' => $category->id,
            'title' => 'Report with Image',
        ]);

        $response = $this->actingAs($student, 'student')
            ->postJson(route('student.lost_found.reports.images.store', $report->id), [
                'image' => UploadedFile::fake()->image('reference.jpg', 300, 300),
            ]);

        $response->assertStatus(201);
        $this->assertEquals(1, $report->images()->count());

        $this->assertNotEmpty(Storage::disk('lost_found_private')->allFiles());
        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    public function test_image_exceeding_configured_byte_limit_is_rejected_at_http_validation(): void
    {
        $student = $this->createStudent();
        $category = $this->createCategory();

        $reportAppService = app(StudentReportApplicationService::class);
        $report = $reportAppService->createLostReport($student, [
            'category_id' => $category->id,
            'title' => 'Report with Oversized Image',
        ]);

        // 3000 KB > 2048 KB (2 MiB limit)
        $oversizedFile = UploadedFile::fake()->create('huge.jpg', 3000, 'image/jpeg');

        $response = $this->actingAs($student, 'student')
            ->postJson(route('student.lost_found.reports.images.store', $report->id), [
                'image' => $oversizedFile,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);

        $this->assertEquals(0, $report->images()->count());
        $this->assertEmpty(Storage::disk('lost_found_private')->allFiles());
    }

    public function test_cross_student_image_upload_returns_404_without_side_effects(): void
    {
        $student1 = $this->createStudent();
        $student2 = $this->createStudent();
        $category = $this->createCategory();

        $reportAppService = app(StudentReportApplicationService::class);
        $report1 = $reportAppService->createLostReport($student1, [
            'category_id' => $category->id,
            'title' => 'Student 1 Image Report',
        ]);

        $response = $this->actingAs($student2, 'student')
            ->postJson(route('student.lost_found.reports.images.store', $report1->id), [
                'image' => UploadedFile::fake()->image('unauthorized.jpg', 300, 300),
            ]);

        $response->assertStatus(404);
        $this->assertEquals(0, $report1->images()->count());
        $this->assertEmpty(Storage::disk('lost_found_private')->allFiles());
        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    public function test_validation_failure_prevents_db_and_file_side_effects(): void
    {
        $student = $this->createStudent();

        $response = $this->actingAs($student, 'student')
            ->postJson(route('student.lost_found.reports.store'), [
                'title' => '',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category_id', 'title']);

        $this->assertEquals(0, LostReport::count());
    }
}
