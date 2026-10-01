<?php

use Illuminate\Http\Request;
use Webkul\Core\Contracts\AuthenticationRedirectResolver;

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

    expect($authSource)->not->toContain('Webkul\\Student', 'Webkul\\LostAndFound', 'Webkul\\Website')
        ->and($bootstrapSource)->not->toContain('student.login', "is('student/*')")
        ->and(config('auth.defaults.guard'))->toBe('user')
        ->and(config('auth.guards.user.provider'))->toBe('users')
        ->and(config('auth.providers.users.model'))->toBe(Webkul\User\Models\User::class);
});

it('resolves guest destinations from their owning package contributions', function () {
    $resolver = app(AuthenticationRedirectResolver::class);

    expect($resolver->resolve(Request::create('/admin/dashboard')))
        ->toBe(route('admin.session.create'));
});

it('keeps Foundation production source free of deleted package ownership', function () {
    $forbidden = [
        'Webkul\\Student',
        'Webkul\\LostAndFound',
        'Webkul\\Website',
        'Webkul\\Web',
        'student::',
        'website::',
        'web::',
        'lost_found::',
    ];

    foreach (['Admin', 'Core', 'DataGrid', 'Installer', 'User'] as $pkg) {
        $source = productionSource(base_path("packages/Webkul/{$pkg}/src"));
        expect($source)->not->toContain(...$forbidden);
    }
});

it('declares the proven internal package dependency graph', function () {
    $expected = [
        'Admin' => ['krayin/laravel-core', 'krayin/laravel-datagrid', 'krayin/laravel-user'],
        'Core' => [],
        'DataGrid' => ['krayin/laravel-core'],
        'Installer' => ['krayin/laravel-core', 'krayin/laravel-user'],
        'User' => ['krayin/laravel-core'],
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
