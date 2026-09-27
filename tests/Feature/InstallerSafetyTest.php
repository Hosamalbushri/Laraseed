<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Webkul\Installer\Helpers\DatabaseManager;

function useInstallerSafetyDatabase(string $path): void
{
    config()->set('database.default', 'installer_safety');
    config()->set('database.connections.installer_safety', [
        'driver' => 'sqlite',
        'database' => $path,
        'prefix' => '',
        'foreign_key_constraints' => true,
    ]);

    DB::purge('installer_safety');
}

afterEach(function () {
    DB::disconnect('installer_safety');
    config()->set('database.default', 'sqlite');
});

it('allows non-destructive installation into an empty disposable database', function () {
    $path = tempnam(sys_get_temp_dir(), 'campushub-empty-');
    useInstallerSafetyDatabase($path);

    $manager = app(DatabaseManager::class);

    expect($manager->isDatabaseEmpty())->toBeTrue();
    expect($manager->migration()->getStatusCode())->toBe(200);
    expect(Schema::hasTable('users'))->toBeTrue();

    DB::disconnect('installer_safety');
    unlink($path);
});

it('refuses a populated database with a missing user and preserves its data', function () {
    $path = tempnam(sys_get_temp_dir(), 'campushub-populated-');
    useInstallerSafetyDatabase($path);

    DB::statement('CREATE TABLE audit_sentinel (id INTEGER PRIMARY KEY, value TEXT NOT NULL)');
    DB::table('audit_sentinel')->insert(['id' => 1, 'value' => 'preserve-me']);

    try {
        app(DatabaseManager::class)->migration();
        $this->fail('The installer accepted a populated database.');
    } catch (HttpException $exception) {
        expect($exception->getStatusCode())->toBe(409);
    }

    expect(DB::table('audit_sentinel')->value('value'))->toBe('preserve-me');

    DB::disconnect('installer_safety');
    unlink($path);
});

it('fails closed for an ambiguous populated schema and preserves every table', function () {
    $path = tempnam(sys_get_temp_dir(), 'campushub-ambiguous-');
    useInstallerSafetyDatabase($path);

    DB::statement('CREATE TABLE legacy_records (id INTEGER PRIMARY KEY, value TEXT NOT NULL)');
    DB::table('legacy_records')->insert(['id' => 7, 'value' => 'legacy-data']);

    expect(fn () => app(DatabaseManager::class)->migration())
        ->toThrow(HttpException::class);
    expect(DB::table('legacy_records')->where('id', 7)->value('value'))->toBe('legacy-data');

    DB::disconnect('installer_safety');
    unlink($path);
});

it('makes web installation unavailable in production', function () {
    $path = tempnam(sys_get_temp_dir(), 'campushub-production-');
    useInstallerSafetyDatabase($path);
    app()->detectEnvironment(fn () => 'production');

    try {
        app(DatabaseManager::class)->migration();
        $this->fail('The web installer was available in production.');
    } catch (HttpException $exception) {
        expect($exception->getStatusCode())->toBe(403);
    } finally {
        app()->detectEnvironment(fn () => 'testing');
    }

    expect(Schema::getTableListing())->toBe([]);

    DB::disconnect('installer_safety');
    unlink($path);
});
