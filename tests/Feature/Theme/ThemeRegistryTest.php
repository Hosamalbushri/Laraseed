<?php

use Webkul\Theme\Definitions\ThemeDefinition;
use Webkul\Theme\Exceptions\ThemeNotFoundException;
use Webkul\Theme\Registry\ThemeRegistry;

it('registers and retrieves valid themes', function () {
    $tempDir = sys_get_temp_dir() . '/test_registry_' . uniqid();
    mkdir($tempDir, 0777, true);

    $registry = new ThemeRegistry();

    $theme = new ThemeDefinition(
        id: 'modern',
        name: 'Modern Theme',
        basePath: $tempDir
    );

    $registry->register($theme);

    expect($registry->has('modern'))->toBeTrue();
    expect($registry->has('MODERN'))->toBeTrue(); // Case-insensitive check
    expect($registry->get('modern')->name)->toBe('Modern Theme');
    expect($registry->get('MODERN')->id)->toBe('modern');

    expect(fn () => $registry->get('unknown'))->toThrow(ThemeNotFoundException::class);

    exec('rm -rf ' . escapeshellarg($tempDir));
});

it('rejects duplicate theme registration with the same normalized ID', function () {
    $tempDir = sys_get_temp_dir() . '/test_dup_reg_' . uniqid();
    mkdir($tempDir, 0777, true);

    $registry = new ThemeRegistry();

    $theme1 = new ThemeDefinition(id: 'duplicate-id', name: 'First', basePath: $tempDir);
    $theme2 = new ThemeDefinition(id: 'DUPLICATE-ID', name: 'Second', basePath: $tempDir);

    $registry->register($theme1);

    expect(fn () => $registry->register($theme2))->toThrow(InvalidArgumentException::class);

    exec('rm -rf ' . escapeshellarg($tempDir));
});

it('lists all registered themes sorted deterministically by ID', function () {
    $tempDir = sys_get_temp_dir() . '/test_sort_reg_' . uniqid();
    mkdir($tempDir, 0777, true);

    $registry = new ThemeRegistry();

    $registry->register(new ThemeDefinition(id: 'zebra', name: 'Zebra', basePath: $tempDir));
    $registry->register(new ThemeDefinition(id: 'alpha', name: 'Alpha', basePath: $tempDir));
    $registry->register(new ThemeDefinition(id: 'beta', name: 'Beta', basePath: $tempDir));

    $all = $registry->all();

    expect(array_keys($all))->toBe(['alpha', 'beta', 'zebra']);

    exec('rm -rf ' . escapeshellarg($tempDir));
});
