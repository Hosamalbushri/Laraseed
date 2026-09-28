<?php

namespace Tests\Feature\LostAndFound;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\TestCase;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

class PackageOwnershipTest extends TestCase
{
    use DatabaseTransactions;

    public function test_package_acl_is_merged_once_and_discoverable_by_roles(): void
    {
        $package = require base_path('packages/Webkul/LostAndFound/src/Config/acl.php');
        $admin = require base_path('packages/Webkul/Admin/src/Config/acl.php');
        $registered = config('acl');

        $this->assertCount(16, $package);
        $this->assertCount(16, array_filter($registered, fn (array $entry): bool => str_starts_with($entry['key'], 'lost_found')));
        $this->assertEmpty(array_filter($admin, fn (array $entry): bool => str_starts_with($entry['key'], 'lost_found')));
        foreach ([...$admin, ...$package] as $entry) {
            $this->assertSame(
                1,
                count(array_filter($registered, fn (array $registeredEntry): bool => $registeredEntry['key'] === $entry['key'])),
                "ACL key [{$entry['key']}] must be registered exactly once.",
            );
        }

        $this->assertSame(count($registered), count(array_unique(array_column($registered, 'key'))));

        $roleItems = acl()->getItems()->firstWhere('key', 'lost_found');
        $this->assertNotNull($roleItems);
        $this->assertNotNull($roleItems->children->firstWhere('key', 'lost_found.items'));
        $this->assertNotNull($roleItems->children->firstWhere('key', 'lost_found.claims'));
        $this->assertSame('Lost & Found Management', trans('lost_found::app.acl.management', [], 'en'));
        $this->assertSame('lost_found.items.view', acl()->getRoles()['admin.lost_found.items.index']);
        $this->assertSame('lost_found.claims.view', acl()->getRoles()['admin.lost_found.claims.show']);
        $this->assertSame('lost_found.claims.approve', acl()->getRoles()['admin.lost_found.claims.revoke']);

        $localizedKeys = [
            'admin.claims.reviewed_success', 'admin.claims.approved_success',
            'admin.claims.rejected_success', 'admin.claims.revoked_success',
            'admin.items.created_success', 'admin.items.updated_success',
            'admin.items.image_uploaded_success', 'admin.custody.received_success',
            'admin.custody.transferred_success', 'admin.custody.moved_success',
            'student.claims.submitted_success', 'student.claims.evidence_added_success',
            'student.claims.image_added_success', 'student.claims.withdrawn_success',
            'student.reports.created_success', 'student.reports.updated_success',
            'student.reports.image_uploaded_success',
        ];

        foreach (['ar', 'en', 'es', 'fa', 'pt_BR', 'tr', 'vi'] as $locale) {
            $translations = require base_path("packages/Webkul/LostAndFound/src/Resources/lang/{$locale}/app.php");
            foreach ($package as $entry) {
                $this->assertTrue(Arr::has($translations, substr($entry['name'], strlen('lost_found::app.'))));
            }
            foreach ($localizedKeys as $key) {
                $this->assertTrue(Arr::has($translations, $key), "Missing {$locale} translation: {$key}");
            }
        }
    }

    public function test_existing_custom_role_key_still_authorizes_package_route(): void
    {
        $role = Role::create([
            'name' => 'Lost found reader '.Str::random(6),
            'permission_type' => 'custom',
            'permissions' => ['lost_found.items.view'],
        ]);
        $user = User::create([
            'name' => 'Reader '.Str::random(6),
            'email' => Str::random(10).'@example.test',
            'password' => bcrypt('password'),
            'status' => 1,
            'role_id' => $role->id,
        ]);

        $this->actingAs($user, 'user')
            ->getJson(route('admin.lost_found.items.index'))
            ->assertOk();

        $this->assertTrue($user->hasPermission('lost_found.items.view'));
        $this->assertTrue(bouncer()->hasPermission('lost_found.items.view'));
        $this->actingAs($user, 'user')
            ->getJson(route('admin.lost_found.claims.show', 999999))
            ->assertStatus(401);
    }

    public function test_employee_and_student_routes_have_expected_middleware_and_no_duplicate_names(): void
    {
        $routes = collect(app('router')->getRoutes()->getRoutes());
        $employee = $routes->filter(fn ($route): bool => str_starts_with((string) $route->getName(), 'admin.lost_found.'));
        $student = $routes->filter(fn ($route): bool => str_starts_with((string) $route->getName(), 'shop.student.lost_found.'));

        $this->assertCount(14, $employee);
        $this->assertCount(7, $student);
        $this->assertCount(21, $employee->merge($student)->pluck('action.as')->unique());

        foreach ($employee as $route) {
            $this->assertStringStartsWith(trim(config('app.admin_path'), '/').'/lost-found/', $route->uri());
            $this->assertEqualsCanonicalizing(['web', 'admin_locale', 'user'], $route->gatherMiddleware());
            $this->assertArrayHasKey($route->getName(), acl()->getRoles());
        }

        foreach ($student as $route) {
            $this->assertStringStartsWith('student/lost-found/', $route->uri());
            $this->assertEqualsCanonicalizing(['web', 'admin_locale', 'auth:student'], $route->gatherMiddleware());
        }
    }

    public function test_nondefault_admin_path_applies_to_package_employee_routes(): void
    {
        $process = new Process(['php', 'artisan', 'route:list', '--json'], base_path(), [
            'APP_ADMIN_PATH' => 'backoffice',
        ]);
        $process->mustRun();

        $routes = collect(json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR));
        $employee = $routes->filter(fn (array $route): bool => str_starts_with($route['name'] ?? '', 'admin.lost_found.'));

        $this->assertCount(14, $employee);
        foreach ($employee as $route) {
            $this->assertStringStartsWith('backoffice/lost-found/', $route['uri']);
        }
    }
}
