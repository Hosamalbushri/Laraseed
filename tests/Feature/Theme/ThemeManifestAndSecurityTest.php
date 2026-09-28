<?php

use Webkul\Theme\Definitions\ThemeDefinition;
use Webkul\Theme\Exceptions\InvalidThemeManifestException;
use Webkul\Theme\Exceptions\InvalidThemePathException;

it('parses and validates a valid theme definition array', function () {
    $tempDir = sys_get_temp_dir() . '/test_theme_' . uniqid();
    mkdir($tempDir . '/views', 0777, true);

    $theme = ThemeDefinition::fromArray([
        'id' => 'custom-blue',
        'name' => 'Custom Blue Theme',
        'parent' => 'default',
        'version' => '2.1.0',
    ], $tempDir);

    expect($theme->id)->toBe('custom-blue');
    expect($theme->name)->toBe('Custom Blue Theme');
    expect($theme->parent)->toBe('default');
    expect($theme->version)->toBe('2.1.0');
    expect($theme->basePath)->toBe(realpath($tempDir));

    exec('rm -rf ' . escapeshellarg($tempDir));
});

it('parses and validates a theme definition from a valid theme.json file', function () {
    $tempDir = sys_get_temp_dir() . '/test_manifest_theme_' . uniqid();
    mkdir($tempDir . '/views', 0777, true);

    file_put_contents($tempDir . '/theme.json', json_encode([
        'id' => 'green-campus',
        'name' => 'Green Campus Theme',
        'version' => '1.0.0',
    ]));

    $theme = ThemeDefinition::fromManifestFile($tempDir . '/theme.json');

    expect($theme->id)->toBe('green-campus');
    expect($theme->name)->toBe('Green Campus Theme');
    expect($theme->parent)->toBeNull();

    exec('rm -rf ' . escapeshellarg($tempDir));
});

it('rejects invalid or unsafe theme IDs', function (string $invalidId) {
    $tempDir = sys_get_temp_dir() . '/test_invalid_theme_' . uniqid();
    mkdir($tempDir, 0777, true);

    expect(fn () => new ThemeDefinition(
        id: $invalidId,
        name: 'Invalid Theme',
        basePath: $tempDir
    ))->toThrow(InvalidThemeManifestException::class);

    exec('rm -rf ' . escapeshellarg($tempDir));
})->with([
    'empty string' => '',
    'path traversal dot dot' => '../evil',
    'nested path traversal' => 'evil/../path',
    'forward slash' => 'theme/sub',
    'backslash' => 'theme\\sub',
    'space in id' => 'invalid id',
    'special symbols' => 'theme@bad!',
    'leading hyphen' => '-invalid',
]);

it('rejects theme paths that attempt directory traversal or absolute non-existent paths', function (string $invalidPath) {
    expect(fn () => new ThemeDefinition(
        id: 'valid-theme',
        name: 'Valid Name',
        basePath: $invalidPath
    ))->toThrow(InvalidThemePathException::class);
})->with([
    '/non/existent/path/that/does/not/exist',
    '/tmp/../non_existent_folder_abc123',
    sys_get_temp_dir() . '/non_existent_manifest_' . uniqid() . '/theme.json',
    '../../relative/path/not/found',
]);

it('rejects malformed or empty manifest JSON files', function () {
    $tempDir = sys_get_temp_dir() . '/test_malformed_manifest_' . uniqid();
    mkdir($tempDir, 0777, true);

    file_put_contents($tempDir . '/theme.json', '{invalid-json');

    expect(fn () => ThemeDefinition::fromManifestFile($tempDir . '/theme.json'))
        ->toThrow(InvalidThemeManifestException::class);

    file_put_contents($tempDir . '/theme.json', '');

    expect(fn () => ThemeDefinition::fromManifestFile($tempDir . '/theme.json'))
        ->toThrow(InvalidThemeManifestException::class);

    exec('rm -rf ' . escapeshellarg($tempDir));
});

it('rejects manifests missing required fields', function (array $manifest) {
    $tempDir = sys_get_temp_dir() . '/test_missing_manifest_' . uniqid();
    mkdir($tempDir, 0777, true);

    expect(fn () => ThemeDefinition::fromArray($manifest, $tempDir))
        ->toThrow(InvalidThemeManifestException::class);

    exec('rm -rf ' . escapeshellarg($tempDir));
})->with([
    'missing id' => [['name' => 'No ID Theme']],
    'missing name' => [['id' => 'valid-id']],
    'empty name' => [['id' => 'valid-id', 'name' => '   ']],
]);
