<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Webkul\Event\DataGrids\Admin\EventDataGrid;
use Webkul\Event\Http\Controllers\Admin\EventController;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

uses(DatabaseTransactions::class);

function eventIsolationUser(array $permissions, string $permissionType = 'custom'): User
{
    $role = Role::create([
        'name' => 'Event isolation '.uniqid(),
        'description' => 'Disposable Event isolation role',
        'permission_type' => $permissionType,
        'permissions' => $permissions,
    ]);

    return User::create([
        'name' => 'Event Isolation User',
        'email' => uniqid('event-isolation-').'@example.test',
        'password' => Hash::make('event-isolation-password'),
        'role_id' => $role->id,
        'status' => 1,
        'view_permission' => 'global',
    ]);
}

it('preserves the complete Event Admin route contract under Event-owned controllers', function () {
    $expected = [
        'admin.events.index' => ['GET', 'admin/events/events', EventController::class.'@index'],
        'admin.events.create' => ['GET', 'admin/events/events/create', EventController::class.'@create'],
        'admin.events.store' => ['POST', 'admin/events/events/create', EventController::class.'@store'],
        'admin.events.edit' => ['GET', 'admin/events/events/edit/{id}', EventController::class.'@edit'],
        'admin.events.update' => ['PUT', 'admin/events/events/edit/{id}', EventController::class.'@update'],
        'admin.events.search' => ['GET', 'admin/events/events/search', EventController::class.'@search'],
        'admin.events.delete' => ['DELETE', 'admin/events/events/{id}', EventController::class.'@destroy'],
        'admin.events.categories.tree' => ['GET', 'admin/events/categories/tree', 'Webkul\\Event\\Http\\Controllers\\Admin\\EventCategoryController@tree'],
        'admin.events.categories.index' => ['GET', 'admin/events/categories', 'Webkul\\Event\\Http\\Controllers\\Admin\\EventCategoryController@index'],
        'admin.events.categories.create' => ['GET', 'admin/events/categories/create', 'Webkul\\Event\\Http\\Controllers\\Admin\\EventCategoryController@create'],
        'admin.events.categories.store' => ['POST', 'admin/events/categories/create', 'Webkul\\Event\\Http\\Controllers\\Admin\\EventCategoryController@store'],
        'admin.events.categories.edit' => ['GET', 'admin/events/categories/edit/{id}', 'Webkul\\Event\\Http\\Controllers\\Admin\\EventCategoryController@edit'],
        'admin.events.categories.update' => ['PUT', 'admin/events/categories/edit/{id}', 'Webkul\\Event\\Http\\Controllers\\Admin\\EventCategoryController@update'],
        'admin.events.categories.delete' => ['DELETE', 'admin/events/categories/{id}', 'Webkul\\Event\\Http\\Controllers\\Admin\\EventCategoryController@destroy'],
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
        ->filter(fn ($route): bool => str_starts_with((string) $route->getName(), 'admin.events.'))
        ->count())->toBe(14);

    $studentSubscriptionRoutes = [
        'admin.students.subscriptions.store' => ['POST', 'admin/students/{id}/subscriptions', 'Webkul\\Event\\Http\\Controllers\\Admin\\EventSubscriptionController@store'],
        'admin.students.subscriptions.delete' => ['DELETE', 'admin/students/{id}/subscriptions/{eventId}', 'Webkul\\Event\\Http\\Controllers\\Admin\\EventSubscriptionController@destroy'],
    ];

    foreach ($studentSubscriptionRoutes as $name => [$method, $uri, $action]) {
        $route = app('router')->getRoutes()->getByName($name);

        expect($route)->not->toBeNull()
            ->and($route->methods())->toContain($method)
            ->and($route->uri())->toBe($uri)
            ->and($route->getActionName())->toBe($action)
            ->and($route->gatherMiddleware())->toContain('web', 'admin_locale', 'user');
    }
});

it('keeps exact Event authorization behavior', function () {
    $this->get(route('admin.events.index'))
        ->assertRedirect(route('admin.session.create'))
        ->assertStatus(302);

    $this->flushSession();

    $this->actingAs(eventIsolationUser(['students']), 'user')
        ->get(route('admin.events.index'))
        ->assertStatus(401);

    $this->actingAs(eventIsolationUser(['events']), 'user')
        ->get(route('admin.events.index'))
        ->assertOk();
});

it('merges Event ACL and menu contributions with stable keys', function () {
    $acl = collect(config('acl'))->keyBy('key');
    $menu = collect(config('menu.admin'))->keyBy('key');

    expect($acl->keys()->all())->toContain(
        'events',
        'events.create',
        'events.edit',
        'events.delete',
        'events.categories',
        'events.categories.create',
        'events.categories.edit',
        'events.categories.delete',
        'students.manage-subscriptions',
    )->and($menu->keys()->all())->toContain('events', 'events.event', 'events.categories');

});

it('renders an Event-owned Admin view and resolves its DataGrid', function () {
    $this->actingAs(eventIsolationUser([], 'all'), 'user')
        ->get(route('admin.events.index'))
        ->assertOk()
        ->assertSee(trans('event::app.events.index.title'));

    $dataGrid = app(EventDataGrid::class);

    expect($dataGrid)->toBeInstanceOf(EventDataGrid::class)
        ->and($dataGrid->prepareQueryBuilder()->toSql())->toContain('event_event_category');
});

it('loads Event routes views translations ACL menu migrations and dashboard contributions', function () {
    expect(view()->exists('event::admin.events.index'))->toBeTrue()
        ->and(trans('event::app.events.index.title'))->not->toBe('event::app.events.index.title')
        ->and(app('router')->getRoutes()->getByName('admin.events.index'))->not->toBeNull()
        ->and(collect(config('acl'))->contains('key', 'events'))->toBeTrue()
        ->and(collect(config('menu.admin'))->contains('key', 'events'))->toBeTrue()
        ->and(app(\Webkul\Admin\Helpers\DashboardStatsRegistry::class)->has('events-status-distribution'))->toBeTrue();

    $migrationPaths = app('migrator')->paths();

    expect(array_map('realpath', $migrationPaths))
        ->toContain(realpath(base_path('packages/Webkul/Event/src/Database/Migrations')));

    $coreConfig = collect(config('core_config'))->keyBy('key');

    expect($coreConfig)->toHaveKey('general.store.events_page')
        ->and(collect($coreConfig['general.settings.menu']['fields'])->pluck('name')->all())
        ->toContain('events', 'events.event', 'events.categories');
});

it('keeps Event dashboard and Student subscription presentation contributed by Event', function () {
    $admin = eventIsolationUser([], 'all');
    $student = \Webkul\Student\Models\Student::create([
        'name' => 'Event package student',
        'university_card_number' => 'EVENT-'.uniqid(),
        'password' => 'event-student-password',
    ]);

    $this->actingAs($admin, 'user')
        ->get(route('admin.dashboard.index'))
        ->assertOk()
        ->assertSee('v-dashboard-events-students-over-all', false)
        ->assertSee('v-dashboard-top-subscribed-events', false);

    $this->actingAs($admin, 'user')
        ->get(route('admin.students.view', $student->id))
        ->assertOk()
        ->assertSee(trans('event::app.students.subscriptions.title'));

    $this->actingAs($admin, 'user')
        ->getJson(route('admin.dashboard.stats', ['type' => 'events-status-distribution']))
        ->assertOk()
        ->assertJsonStructure(['statistics' => [['name', 'total']], 'date_range']);
});

it('leaves Foundation package source free of Event feature ownership', function () {
    $roots = ['Core', 'Admin', 'User', 'DataGrid', 'Installer'];
    $forbidden = [
        'Webkul\\Event\\',
        'event_student',
        'event_categories',
        'event_subscriptions',
        'general.store.events_page',
        "'name' => 'show_events'",
        "'name' => 'events_label'",
        'admin.events.',
        'events.create',
        'events.edit',
        'events.delete',
        'event::',
        'EventProxy',
        'EventRepository',
        'EventSubscription',
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
            ->not->toContain('event');
    }
});

it('distinguishes allowed root composition metadata from Foundation package source', function () {
    $compositionReferences = [
        'bootstrap/providers.php' => [
            'use Webkul\\Event\\Providers\\EventServiceProvider;',
            'EventServiceProvider::class',
        ],
        'config/concord.php' => [
            'use Webkul\\Event\\Providers\\ModuleServiceProvider as EventModuleServiceProvider;',
            'EventModuleServiceProvider::class',
        ],
        'composer.json' => ['Webkul\\\\Event\\\\'],
    ];

    foreach ($compositionReferences as $file => $references) {
        foreach ($references as $reference) {
            expect(file_get_contents(base_path($file)))
                ->toContain($reference);
        }
    }
});

it('keeps Event static translation keys in parity across shipped locales', function () {
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

    $reference = $flatten(require base_path('packages/Webkul/Event/src/Resources/lang/en/app.php'));

    foreach (['ar', 'es', 'fa', 'pt_BR', 'tr', 'vi'] as $locale) {
        expect($flatten(require base_path("packages/Webkul/Event/src/Resources/lang/$locale/app.php")))
            ->toBe($reference, "Event translation keys differ for $locale");
    }
});
