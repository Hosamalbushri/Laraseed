<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Webkul\Admin\Helpers\MegaSearch;
use Webkul\Core\Contracts\AuthenticationRedirectResolver;
use Webkul\Student\Models\Student;

function productionSource(string $path): string
{
    $source = '';
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
        $path,
        FilesystemIterator::SKIP_DOTS,
    ));

    foreach ($iterator as $file) {
        if ($file->isFile() && ! str_contains($file->getPathname(), '/assets/')) {
            $source .= file_get_contents($file->getPathname())."\n";
        }
    }

    return $source;
}

it('keeps root authentication and bootstrap configuration optional-identity neutral', function () {
    $authSource = file_get_contents(config_path('auth.php'));
    $bootstrapSource = file_get_contents(base_path('bootstrap/app.php'));

    expect($authSource)->not->toContain('Webkul\\Student', "'student'", "'students'")
        ->and($bootstrapSource)->not->toContain('student.login', "is('student/*')")
        ->and(config('auth.guards.student'))->toBe([
            'driver' => 'session',
            'provider' => 'students',
        ])
        ->and(config('auth.providers.students.model'))->toBe(Student::class);
});

it('resolves guest destinations from their owning package contributions', function () {
    $resolver = app(AuthenticationRedirectResolver::class);

    expect($resolver->resolve(Request::create('/student/lost-found/reports')))
        ->toBe(route('student.login'))
        ->and($resolver->resolve(Request::create('/admin/dashboard')))
        ->toBe(route('admin.session.create'));
});

it('keeps Admin and Web production source free of Student ownership', function () {
    $adminSource = productionSource(base_path('packages/Webkul/Admin/src'));
    $webSource = productionSource(base_path('packages/Webkul/Web/src'));
    $forbidden = [
        'Webkul\\Student',
        'admin.students',
        'student.login',
        'student::',
        'student_portal',
        "'students.create'",
    ];

    expect($adminSource)->not->toContain(...$forbidden)
        ->and($webSource)->not->toContain(...$forbidden);
});

it('keeps Student Admin contributions owned by Student', function () {
    $provider = file_get_contents(base_path('packages/Webkul/Student/src/Providers/StudentServiceProvider.php'));
    $tabs = collect(app(MegaSearch::class)->tabs())->keyBy('key');

    expect($tabs)->toHaveKey('students')
        ->and($tabs['students']['endpoint'])->toBe(route('admin.students.search'))
        ->and($provider)->toContain(
            'student::admin.layouts.header.desktop-mega-search-results',
            'student::admin.layouts.header.mobile-mega-search-results',
            'student::admin.layouts.header.quick-creation-item',
        );
});

it('keeps the LostAndFound private disk package-owned and storage-compatible', function () {
    $rootFilesystem = file_get_contents(config_path('filesystems.php'));
    $packageFilesystem = file_get_contents(base_path('packages/Webkul/LostAndFound/src/Config/filesystems.php'));

    expect($rootFilesystem)->not->toContain('lost_found_private', 'lost-found-private')
        ->and($packageFilesystem)->toContain('lost_found_private', "storage_path('app/lost-found-private')")
        ->and(config('filesystems.disks.lost_found_private'))->toMatchArray([
            'driver' => 'local',
            'root' => storage_path('app/lost-found-private'),
            'throw' => true,
        ])
        ->and(Storage::disk('lost_found_private'))->not->toBeNull();
});

it('declares the proven internal package dependency graph', function () {
    $expected = [
        'Admin' => ['krayin/laravel-core', 'krayin/laravel-datagrid', 'krayin/laravel-user'],
        'Core' => [],
        'DataGrid' => ['krayin/laravel-core'],
        'Installer' => ['krayin/laravel-core', 'krayin/laravel-user'],
        'LostAndFound' => ['krayin/laravel-admin', 'krayin/laravel-core', 'krayin/laravel-datagrid', 'krayin/laravel-user', 'webkul/student'],
        'Student' => ['krayin/laravel-admin', 'krayin/laravel-core', 'krayin/laravel-datagrid'],
        'Theme' => [],
        'User' => ['krayin/laravel-core'],
        'Web' => ['krayin/laravel-core', 'webkul/theme'],
    ];

    foreach ($expected as $package => $dependencies) {
        $manifest = json_decode(
            file_get_contents(base_path("packages/Webkul/$package/composer.json")),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        $declared = array_values(array_filter(
            array_keys($manifest['require'] ?? []),
            fn (string $name): bool => str_starts_with($name, 'krayin/') || str_starts_with($name, 'webkul/'),
        ));
        sort($declared);
        sort($dependencies);

        expect($declared)->toBe($dependencies, "$package internal dependencies differ");
    }
});
