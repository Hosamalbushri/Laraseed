<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Webkul\Student\Models\Student;
use Webkul\Student\Services\Contracts\UniversityStudentApiContract;
use Webkul\Student\Services\FakeUniversityStudentApiClient;
use Webkul\Student\Services\UniversityStudentApiClient;

uses(DatabaseTransactions::class);

afterEach(function () {
    app()->detectEnvironment(fn () => 'testing');
    config()->set('student.university.fake', true);
    app()->forgetInstance(UniversityStudentApiContract::class);
});

it('allows the fake university client outside production', function () {
    app()->detectEnvironment(fn () => 'local');
    config()->set('student.university.fake', true);
    app()->forgetInstance(UniversityStudentApiContract::class);

    expect(app(UniversityStudentApiContract::class))
        ->toBeInstanceOf(FakeUniversityStudentApiClient::class);
});

it('selects the production university client when fake mode is disabled', function () {
    app()->detectEnvironment(fn () => 'production');
    config()->set('student.university.fake', false);
    app()->forgetInstance(UniversityStudentApiContract::class);

    expect(app(UniversityStudentApiContract::class))
        ->toBeInstanceOf(UniversityStudentApiClient::class);
});

it('refuses to resolve fake university authentication in production', function () {
    app()->detectEnvironment(fn () => 'production');
    config()->set('student.university.fake', true);
    app()->forgetInstance(UniversityStudentApiContract::class);

    expect(fn () => app(UniversityStudentApiContract::class))
        ->toThrow(LogicException::class, 'Fake university authentication is prohibited in production.');
});

it('enforces the production fake-auth guard from cached configuration values', function () {
    $cachedConfiguration = config()->all();
    $cachedConfiguration['student']['university']['fake'] = true;

    config()->set($cachedConfiguration);
    app()->detectEnvironment(fn () => 'production');
    app()->forgetInstance(UniversityStudentApiContract::class);

    expect(fn () => app(UniversityStudentApiContract::class))
        ->toThrow(LogicException::class);
});

it('keeps the student guard isolated from staff routes', function () {
    $student = Student::create([
        'university_card_number' => 'BOUNDARY-'.uniqid(),
        'password' => 'student-password',
        'name' => 'Boundary Student',
    ]);

    $this->actingAs($student, 'student')
        ->get(route('admin.dashboard.index'))
        ->assertRedirect(route('admin.session.create'));
});
