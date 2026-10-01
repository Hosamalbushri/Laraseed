<?php

use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Webkul\User\Models\User;

spl_autoload_register(function (string $class): void {
    $parts = explode('\\', $class);
    if (count($parts) >= 2) {
        $vendor = $parts[0];
        $package = $parts[1];
        $baseDir = dirname(__DIR__) . "/packages/{$vendor}/{$package}";
        if (is_dir($baseDir)) {
            $relative = implode('/', array_slice($parts, 2));
            $srcFile = "{$baseDir}/src/{$relative}.php";
            if (file_exists($srcFile)) {
                require_once $srcFile;
                return;
            }
            if (isset($parts[2]) && $parts[2] === 'Tests') {
                $testRelative = implode('/', array_slice($parts, 3));
                $testFile = "{$baseDir}/tests/{$testRelative}.php";
                if (file_exists($testFile)) {
                    require_once $testFile;
                    return;
                }
            }
        }
    }
});

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
 */

uses(TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
 */

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
 */

/**
 * Get default admin which is created on fresh instance.
 *
 * @return User
 */
function getDefaultAdmin()
{
    $admin = User::find(1);

    return $admin;
}

/**
 * Sanctum authenticated admin.
 *
 * @return User
 */
function actingAsSanctumAuthenticatedAdmin()
{
    return Sanctum::actingAs(
        getDefaultAdmin(),
        ['*']
    );
}

/**
 * Get first name.
 *
 * @param  string  $fullName
 * @return string
 */
function getFirstName($fullName)
{
    return explode(' ', $fullName)[0];
}
