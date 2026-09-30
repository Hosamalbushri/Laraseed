<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Webkul\Student\DataGrids\StudentDataGrid;
use Webkul\Student\Models\Student;
use Webkul\Student\Services\StudentAdminService;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

uses(DatabaseTransactions::class);

function studentReferenceUser(array $permissions, string $permissionType = 'custom'): User
{
    $role = Role::create([
        'name' => 'Student reference '.uniqid(),
        'description' => 'Disposable Student reference role',
        'permission_type' => $permissionType,
        'permissions' => $permissions,
    ]);

    return User::create([
        'name' => 'Student Reference User',
        'email' => uniqid('student-reference-').'@example.test',
        'password' => Hash::make('student-reference-password'),
        'role_id' => $role->id,
        'status' => 1,
        'view_permission' => 'global',
    ]);
}

function validStudentPayload(array $overrides = []): array
{
    return [
        'name' => 'John Doe',
        'university_card_number' => 'CARD-'.uniqid(),
        'registration_number' => 'REG-'.uniqid(),
        'major' => 'Computer Science',
        'academic_level' => 'Senior',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
        ...$overrides,
    ];
}

it('preserves exact Student write authentication authorization validation and success responses', function () {
    $this->post(route('admin.students.store'), validStudentPayload())
        ->assertStatus(302)
        ->assertRedirect(route('admin.session.create'));

    $this->actingAs(studentReferenceUser(['dashboard']), 'user')
        ->post(route('admin.students.store'), validStudentPayload())
        ->assertStatus(401);

    $this->actingAs(studentReferenceUser(['students.create']), 'user')
        ->from(route('admin.students.create'))
        ->post(route('admin.students.store'), [
            'name' => '',
            'university_card_number' => '',
            'password' => 'short',
            'password_confirmation' => 'mismatch',
        ])
        ->assertStatus(302)
        ->assertRedirect(route('admin.students.create'))
        ->assertSessionHasErrors(['name', 'university_card_number', 'password']);

    $card = 'UNI-'.uniqid();
    $this->actingAs(studentReferenceUser([], 'all'), 'user')
        ->post(route('admin.students.store'), validStudentPayload([
            'university_card_number' => $card,
            'name' => 'Created via FormRequest',
        ]))
        ->assertStatus(302)
        ->assertRedirect(route('admin.students.index'));

    $this->assertDatabaseHas('students', [
        'university_card_number' => $card,
        'name' => 'Created via FormRequest',
    ]);
});

it('validates unique university card numbers on create and update through FormRequests', function () {
    $user = studentReferenceUser([], 'all');
    $existingCard = 'EXISTING-'.uniqid();

    $student1 = Student::create([
        'name' => 'First Student',
        'university_card_number' => $existingCard,
        'password' => 'secret123',
    ]);

    $student2 = Student::create([
        'name' => 'Second Student',
        'university_card_number' => 'SECOND-'.uniqid(),
        'password' => 'secret123',
    ]);

    $this->actingAs($user, 'user')
        ->post(route('admin.students.store'), validStudentPayload([
            'university_card_number' => $existingCard,
        ]))
        ->assertStatus(302)
        ->assertSessionHasErrors('university_card_number');

    $this->actingAs($user, 'user')
        ->put(route('admin.students.update', $student2->id), [
            'name' => 'Updated Second Student',
            'university_card_number' => $existingCard,
        ])
        ->assertStatus(302)
        ->assertSessionHasErrors('university_card_number');

    $this->actingAs($user, 'user')
        ->put(route('admin.students.update', $student1->id), [
            'name' => 'Updated Same Card',
            'university_card_number' => $existingCard,
        ])
        ->assertStatus(302)
        ->assertSessionHasNoErrors();

    expect($student1->fresh()->name)->toBe('Updated Same Card');
});

it('handles profile image upload replacement and cleanup on student deletion', function () {
    Storage::fake('public');

    $user = studentReferenceUser([], 'all');
    $imageFile = UploadedFile::fake()->image('avatar.jpg');

    $this->actingAs($user, 'user')
        ->post(route('admin.students.store'), validStudentPayload([
            'profile_image' => $imageFile,
        ]))
        ->assertStatus(302);

    $student = Student::latest('id')->first();
    expect($student->profile_image)->not->toBeNull();
    Storage::disk('public')->assertExists($student->profile_image);

    $oldPath = $student->profile_image;

    $newImage = UploadedFile::fake()->image('avatar2.png');
    $this->actingAs($user, 'user')
        ->put(route('admin.students.update', $student->id), [
            'name' => 'Updated Avatar',
            'university_card_number' => $student->university_card_number,
            'profile_image' => $newImage,
        ])
        ->assertStatus(302);

    $student->refresh();
    expect($student->profile_image)->not->toBe($oldPath);
    Storage::disk('public')->assertMissing($oldPath);
    Storage::disk('public')->assertExists($student->profile_image);

    $currentPath = $student->profile_image;
    $this->actingAs($user, 'user')
        ->delete(route('admin.students.delete', $student->id))
        ->assertOk();

    Storage::disk('public')->assertMissing($currentPath);
    $this->assertDatabaseMissing('students', ['id' => $student->id]);
});

it('supports single delete and mass delete operations safely within transactions', function () {
    $user = studentReferenceUser([], 'all');

    $s1 = Student::create(['name' => 'M1', 'university_card_number' => 'M1-'.uniqid(), 'password' => 'secret123']);
    $s2 = Student::create(['name' => 'M2', 'university_card_number' => 'M2-'.uniqid(), 'password' => 'secret123']);
    $s3 = Student::create(['name' => 'M3', 'university_card_number' => 'M3-'.uniqid(), 'password' => 'secret123']);

    $this->actingAs($user, 'user')
        ->post(route('admin.students.mass_delete'), [
            'indices' => [$s1->id, $s2->id],
        ])
        ->assertOk()
        ->assertJson([
            'message' => trans('student::app.students.all-delete-success'),
        ]);

    $this->assertDatabaseMissing('students', ['id' => $s1->id]);
    $this->assertDatabaseMissing('students', ['id' => $s2->id]);
    $this->assertDatabaseHas('students', ['id' => $s3->id]);
});

it('fires generic DataGrid hooks for external extensions', function () {
    $queryFired = false;
    $columnFired = false;

    Event::listen('admin.students.datagrid.query.after', function () use (&$queryFired) {
        $queryFired = true;
    });

    Event::listen('admin.students.datagrid.columns.after', function () use (&$columnFired) {
        $columnFired = true;
    });

    $dataGrid = app(StudentDataGrid::class);
    $dataGrid->prepareQueryBuilder();
    $dataGrid->prepareColumns();

    expect($queryFired)->toBeTrue()
        ->and($columnFired)->toBeTrue();
});

it('enforces Student isolation and architecture static cleanliness', function () {
    $studentRoot = base_path('packages/Webkul/Student');
    $controllers = implode("\n", array_map(
        fn (string $path): string => file_get_contents($path),
        glob($studentRoot.'/src/Http/Controllers/Admin/*.php'),
    ));

    $eventReferences = [];
    $studentIterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
        $studentRoot.'/src',
        FilesystemIterator::SKIP_DOTS,
    ));

    foreach ($studentIterator as $file) {
        if ($file->isFile()) {
            $contents = file_get_contents($file->getPathname());
            if (str_contains($contents, 'Webkul\\Event') || str_contains($contents, 'event::')) {
                $eventReferences[] = $file->getPathname();
            }
        }
    }

    expect($controllers)
        ->not->toContain('$this->validate(', '->validate(', 'Validator::')
        ->and($eventReferences)->toBe([])
        ->and(file_exists($studentRoot.'/STUDENT-README.md'))->toBeFalse()
        ->and(file_exists($studentRoot.'/ARCHITECTURE.md'))->toBeTrue();
});
