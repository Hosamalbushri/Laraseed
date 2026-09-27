<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Webkul\Admin\Http\Middleware\Bouncer;
use Webkul\Student\Models\Student;
use Webkul\Student\Services\Contracts\UniversityStudentApiContract;
use Webkul\Student\Services\FakeUniversityStudentApiClient;
use Webkul\Student\Services\UniversityStudentApiClient;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

uses(DatabaseTransactions::class);

function boundaryAuditUser(array $permissions, string $permissionType = 'custom'): User
{
    $role = Role::create([
        'name' => 'Boundary audit '.uniqid(),
        'description' => 'Disposable security-boundary role',
        'permission_type' => $permissionType,
        'permissions' => $permissions,
    ]);

    return User::create([
        'name' => 'Boundary Audit User',
        'email' => uniqid('boundary-audit-').'@example.test',
        'password' => Hash::make('boundary-password'),
        'role_id' => $role->id,
        'status' => 1,
        'view_permission' => 'global',
    ]);
}

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

it('allows a custom role on a mapped route with the correct permission', function () {
    $this->actingAs(boundaryAuditUser(['events']), 'user')
        ->get(route('admin.events.index'))
        ->assertOk();
});

it('denies a custom role on a mapped route without its permission', function () {
    $this->actingAs(boundaryAuditUser(['students']), 'user')
        ->get(route('admin.events.index'))
        ->assertUnauthorized();
});

it('denies an unmapped privileged staff route to a custom role', function () {
    Route::middleware(Bouncer::class)
        ->get('/_security-test/unmapped-admin-route', fn () => response('unsafe'))
        ->name('admin.security-test.unmapped');

    $this->actingAs(boundaryAuditUser(['dashboard']), 'user')
        ->get('/_security-test/unmapped-admin-route')
        ->assertUnauthorized();
});

it('preserves full administrator access to an unmapped staff route', function () {
    Route::middleware(Bouncer::class)
        ->get('/_security-test/full-admin-route', fn () => response('allowed'))
        ->name('admin.security-test.full');

    $this->actingAs(boundaryAuditUser([], 'all'), 'user')
        ->get('/_security-test/full-admin-route')
        ->assertOk();
});

it('keeps own-account self-service available to a custom role', function () {
    $this->actingAs(boundaryAuditUser(['dashboard']), 'user')
        ->get(route('admin.user.account.edit'))
        ->assertOk();
});

it('keeps TinyMCE behind its dedicated content upload permission', function () {
    $this->actingAs(boundaryAuditUser(['dashboard']), 'user')
        ->postJson(route('admin.tinymce.upload'))
        ->assertUnauthorized();
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

it('classifies every authenticated staff route as ACL-mapped or explicitly safe', function () {
    $mappedRoutes = acl()->getRoles();
    $safeRoutes = config('acl_safe_routes', []);
    $unclassified = [];

    foreach (app('router')->getRoutes() as $route) {
        $middleware = $route->gatherMiddleware();
        $excluded = $route->excludedMiddleware();

        if (! in_array('user', $middleware, true) || in_array('user', $excluded, true)) {
            continue;
        }

        $name = $route->getName();

        if (! $name || (! isset($mappedRoutes[$name]) && ! in_array($name, $safeRoutes, true))) {
            $unclassified[] = $name ?: $route->uri();
        }
    }

    expect($unclassified)->toBe([]);
});

it('exports authorized DataGrid results as CSV XLS and XLSX', function (string $format) {
    $response = $this->actingAs(boundaryAuditUser(['settings.user.groups']), 'user')
        ->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->get(route('admin.settings.groups.index', ['export' => 1, 'format' => $format]));

    $response->assertOk();
    expect($response->headers->get('content-disposition'))->toContain('.'.$format);
})->with(['csv', 'xls', 'xlsx']);

it('denies DataGrid export without the mapped route permission', function () {
    $this->actingAs(boundaryAuditUser(['students']), 'user')
        ->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->get(route('admin.settings.groups.index', ['export' => 1, 'format' => 'xlsx']))
        ->assertUnauthorized();
});
