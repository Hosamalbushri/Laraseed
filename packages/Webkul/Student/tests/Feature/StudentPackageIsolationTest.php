<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Webkul\Admin\Helpers\MegaSearch;
use Webkul\Student\DataGrids\StudentDataGrid;
use Webkul\Student\Http\Controllers\Admin\StudentController;
use Webkul\Student\Models\Student;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

uses(DatabaseTransactions::class);

function studentIsolationUser(array $permissions, string $permissionType = 'custom'): User
{
    $role = Role::create([
        'name' => 'Student isolation '.uniqid(),
        'description' => 'Disposable Student isolation role',
        'permission_type' => $permissionType,
        'permissions' => $permissions,
    ]);

    return User::create([
        'name' => 'Student Isolation User',
        'email' => uniqid('student-isolation-').'@example.test',
        'password' => Hash::make('student-isolation-password'),
        'role_id' => $role->id,
        'status' => 1,
        'view_permission' => 'global',
    ]);
}

it('preserves the complete Student Admin route contract under Student-owned controllers', function () {
    $expected = [
        'admin.students.index' => ['GET', 'admin/students', StudentController::class.'@index'],
        'admin.students.search' => ['GET', 'admin/students/search', StudentController::class.'@search'],
        'admin.students.create' => ['GET', 'admin/students/create', StudentController::class.'@create'],
        'admin.students.store' => ['POST', 'admin/students/create', StudentController::class.'@store'],
        'admin.students.view' => ['GET', 'admin/students/view/{id}', StudentController::class.'@show'],
        'admin.students.edit' => ['GET', 'admin/students/edit/{id}', StudentController::class.'@edit'],
        'admin.students.update' => ['PUT', 'admin/students/edit/{id}', StudentController::class.'@update'],
        'admin.students.delete' => ['DELETE', 'admin/students/{id}', StudentController::class.'@destroy'],
        'admin.students.mass_delete' => ['POST', 'admin/students/mass-delete', StudentController::class.'@massDestroy'],
    ];

    foreach ($expected as $name => [$method, $uri, $action]) {
        $route = app('router')->getRoutes()->getByName($name);

        expect($route)->not->toBeNull()
            ->and($route->methods())->toContain($method)
            ->and($route->uri())->toBe($uri)
            ->and($route->getActionName())->toBe($action)
            ->and($route->gatherMiddleware())->toContain('web', 'admin_locale', 'user');
    }

    expect(collect(app('router')->getRoutes()->getRoutes())
        ->filter(fn ($route): bool => str_starts_with((string) $route->getName(), 'admin.students.') && ! str_contains((string) $route->getName(), 'subscriptions'))
        ->count())->toBe(9);
});

it('keeps Student authentication separate from the generic Web root', function () {
    expect(route('student.login', absolute: false))->toBe('/student/login');

    $this->get(route('student.login'))->assertOk();

    Route::middleware('auth:student')->get('student/test-auth-guard', fn () => 'ok');

    $this->get('/student/test-auth-guard')
        ->assertRedirect(route('student.login'));
});

it('keeps exact Student authorization behavior', function () {
    $student = Student::create([
        'name' => 'Auth Student',
        'university_card_number' => 'AUTH-'.uniqid(),
        'password' => 'secret123',
    ]);

    $this->get(route('admin.students.index'))
        ->assertRedirect(route('admin.session.create'))
        ->assertStatus(302);

    $this->flushSession();

    $this->actingAs(studentIsolationUser(['dashboard']), 'user')
        ->get(route('admin.students.index'))
        ->assertStatus(401);

    $this->actingAs(studentIsolationUser(['students']), 'user')
        ->get(route('admin.students.index'))
        ->assertOk();

    $this->actingAs(studentIsolationUser(['students.view']), 'user')
        ->get(route('admin.students.view', $student->id))
        ->assertOk();

    $this->actingAs(studentIsolationUser(['students.create']), 'user')
        ->get(route('admin.students.view', $student->id))
        ->assertStatus(401);
});

it('merges Student ACL and menu contributions with stable keys', function () {
    $acl = collect(config('acl'))->keyBy('key');
    $menu = collect(config('menu.admin'))->keyBy('key');

    expect($acl->keys()->all())->toContain(
        'students',
        'students.create',
        'students.edit',
        'students.view',
        'students.delete',
    )->and($menu->keys()->all())->toContain('students');

    expect($menu['students']['route'])->toBe('admin.students.index')
        ->and($menu['students']['name'])->toBe('student::app.students.title')
        ->and($menu['students']['icon-class'])->toBe('icon-contact');
});

it('renders Student-owned Admin views and resolves its DataGrid', function () {
    $student = Student::create([
        'name' => 'DataGrid Student',
        'university_card_number' => 'DG-'.uniqid(),
        'password' => 'secret123',
    ]);

    $this->actingAs(studentIsolationUser([], 'all'), 'user')
        ->get(route('admin.students.index'))
        ->assertOk()
        ->assertSee(trans('student::app.students.index.title'));

    $this->actingAs(studentIsolationUser([], 'all'), 'user')
        ->get(route('admin.students.create'))
        ->assertOk()
        ->assertSee(trans('student::app.students.create.title'));

    $this->actingAs(studentIsolationUser([], 'all'), 'user')
        ->get(route('admin.students.edit', $student->id))
        ->assertOk()
        ->assertSee(trans('student::app.students.edit.title'));

    $this->actingAs(studentIsolationUser([], 'all'), 'user')
        ->get(route('admin.students.view', $student->id))
        ->assertOk()
        ->assertSee($student->name)
        ->assertSee(trans('student::app.students.view.general-info'));

    $dataGrid = app(StudentDataGrid::class);

    expect($dataGrid)->toBeInstanceOf(StudentDataGrid::class)
        ->and($dataGrid->prepareQueryBuilder()->toSql())->toContain('students');
});

it('loads Student routes views translations ACL menu migrations and core config contributions', function () {
    expect(view()->exists('student::admin.students.index'))->toBeTrue()
        ->and(trans('student::app.students.title'))->not->toBe('student::app.students.title')
        ->and(app('router')->getRoutes()->getByName('admin.students.index'))->not->toBeNull()
        ->and(collect(config('acl'))->contains('key', 'students'))->toBeTrue()
        ->and(collect(config('menu.admin'))->contains('key', 'students'))->toBeTrue()
        ->and(collect(app(MegaSearch::class)->tabs())->pluck('key')->all())->toContain('students');

    $migrationPaths = app('migrator')->paths();

    expect(array_map('realpath', $migrationPaths))
        ->toContain(realpath(base_path('packages/Webkul/Student/src/Database/Migrations')));

    $coreConfig = collect(config('core_config'))->keyBy('key');

    expect($coreConfig)->toHaveKey('general.store.student_login')
        ->and($coreConfig)->toHaveKey('general.university_api')
        ->and($coreConfig)->toHaveKey('general.university_api.endpoint_settings')
        ->and(collect($coreConfig['general.settings.menu']['fields'])->pluck('name')->all())
        ->toContain('students');
});

it('leaves Foundation package source free of Student feature ownership', function () {
    $roots = ['Core', 'Admin', 'User', 'DataGrid', 'Installer'];
    $forbidden = [
        'Webkul\\Student\\',
        'student::',
        'StudentDataGrid',
        'general.store.student_login',
        'general.university_api',
        "'name' => 'students'",
    ];
    $violations = [];

    foreach ($roots as $root) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
            base_path("packages/Webkul/$root"),
            FilesystemIterator::SKIP_DOTS,
        ));

        foreach ($iterator as $file) {
            if (! $file->isFile() || str_contains($file->getPathname(), '/node_modules/')) {
                continue;
            }

            $contents = file_get_contents($file->getPathname());

            foreach ($forbidden as $needle) {
                if (str_contains($contents, $needle)) {
                    $violations[] = $file->getPathname().': '.$needle;
                }
            }
        }
    }

    expect($violations)->toBe([]);

    foreach (['acl.php', 'menu.php', 'core_config.php'] as $configFile) {
        expect(strtolower(file_get_contents(base_path("packages/Webkul/Admin/src/Config/$configFile"))))
            ->not->toContain('student');
    }
});

it('derives Student provider composition from package metadata', function () {
    $compositionReferences = [
        'bootstrap/providers.php' => [
            "config('campushub.optional_packages.providers', [])",
            '...$optionalProviders',
        ],
        'composer.json' => ['Webkul\\\\Student\\\\'],
        'packages/Webkul/Student/composer.json' => [
            '"id": "student"',
            '"provider": "Webkul\\\\Student\\\\Providers\\\\StudentServiceProvider"',
            '"concord_module": null',
        ],
    ];

    foreach ($compositionReferences as $file => $references) {
        foreach ($references as $reference) {
            expect(file_get_contents(base_path($file)))
                ->toContain($reference);
        }
    }

    expect(file_get_contents(base_path('bootstrap/providers.php')))
        ->not->toContain('StudentServiceProvider::class');
});

it('keeps Student static translation keys in parity across shipped locales', function () {
    $flatten = function (array $values, string $prefix = '') use (&$flatten): array {
        $keys = [];

        foreach ($values as $key => $value) {
            $path = $prefix === '' ? $key : $prefix.'.'.$key;

            if (is_array($value)) {
                $keys = [...$keys, ...$flatten($value, $path)];
            } else {
                $keys[] = $path;
            }
        }

        sort($keys);

        return $keys;
    };

    $reference = $flatten(require base_path('packages/Webkul/Student/src/Resources/lang/en/app.php'));

    foreach (['ar', 'es', 'fa', 'pt_BR', 'tr', 'vi'] as $locale) {
        expect($flatten(require base_path("packages/Webkul/Student/src/Resources/lang/$locale/app.php")))
            ->toBe($reference, "Student translation keys differ for $locale");
    }
});
