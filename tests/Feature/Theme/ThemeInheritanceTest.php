<?php

use Webkul\Theme\Definitions\ThemeDefinition;
use Webkul\Theme\Exceptions\MissingParentThemeException;
use Webkul\Theme\Exceptions\ThemeInheritanceCycleException;
use Webkul\Theme\Exceptions\ThemeNotFoundException;
use Webkul\Theme\Registry\ThemeRegistry;

it('resolves inheritance chain for a theme without a parent', function () {
    $tempDir = sys_get_temp_dir() . '/test_inh_single_' . uniqid();
    mkdir($tempDir, 0777, true);

    $registry = new ThemeRegistry();
    $theme = new ThemeDefinition(id: 'standalone', name: 'Standalone Theme', basePath: $tempDir);
    $registry->register($theme);

    $chain = $registry->resolveInheritanceChain('standalone');

    expect($chain)->toHaveCount(1);
    expect($chain[0]->id)->toBe('standalone');

    exec('rm -rf ' . escapeshellarg($tempDir));
});

it('resolves multi-level inheritance chain from child to grandparent', function () {
    $tempDir = sys_get_temp_dir() . '/test_inh_multi_' . uniqid();
    mkdir($tempDir, 0777, true);

    $registry = new ThemeRegistry();
    $grandparent = new ThemeDefinition(id: 'base', name: 'Base Theme', basePath: $tempDir);
    $parent = new ThemeDefinition(id: 'modern', name: 'Modern Theme', basePath: $tempDir, parent: 'base');
    $child = new ThemeDefinition(id: 'dark-modern', name: 'Dark Modern', basePath: $tempDir, parent: 'modern');

    $registry->register($grandparent);
    $registry->register($parent);
    $registry->register($child);

    $chain = $registry->resolveInheritanceChain('dark-modern');

    expect($chain)->toHaveCount(3);
    expect($chain[0]->id)->toBe('dark-modern');
    expect($chain[1]->id)->toBe('modern');
    expect($chain[2]->id)->toBe('base');

    exec('rm -rf ' . escapeshellarg($tempDir));
});

it('throws ThemeNotFoundException when resolving unknown theme', function () {
    $registry = new ThemeRegistry();

    expect(fn () => $registry->resolveInheritanceChain('non-existent'))
        ->toThrow(ThemeNotFoundException::class);
});

it('throws MissingParentThemeException when theme references missing parent', function () {
    $tempDir = sys_get_temp_dir() . '/test_inh_missing_' . uniqid();
    mkdir($tempDir, 0777, true);

    $registry = new ThemeRegistry();
    $child = new ThemeDefinition(id: 'child', name: 'Child', basePath: $tempDir, parent: 'missing-parent');
    $registry->register($child);

    expect(fn () => $registry->resolveInheritanceChain('child'))
        ->toThrow(MissingParentThemeException::class);

    exec('rm -rf ' . escapeshellarg($tempDir));
});

it('detects and rejects self-inheritance cycle (A -> A)', function () {
    $tempDir = sys_get_temp_dir() . '/test_inh_self_' . uniqid();
    mkdir($tempDir, 0777, true);

    $registry = new ThemeRegistry();
    $theme = new ThemeDefinition(id: 'self-ref', name: 'Self', basePath: $tempDir, parent: 'self-ref');
    $registry->register($theme);

    expect(fn () => $registry->resolveInheritanceChain('self-ref'))
        ->toThrow(ThemeInheritanceCycleException::class);

    exec('rm -rf ' . escapeshellarg($tempDir));
});

it('detects and rejects two-node inheritance cycle (A -> B -> A)', function () {
    $tempDir = sys_get_temp_dir() . '/test_inh_cycle2_' . uniqid();
    mkdir($tempDir, 0777, true);

    $registry = new ThemeRegistry();
    $themeA = new ThemeDefinition(id: 'theme-a', name: 'Theme A', basePath: $tempDir, parent: 'theme-b');
    $themeB = new ThemeDefinition(id: 'theme-b', name: 'Theme B', basePath: $tempDir, parent: 'theme-a');

    $registry->register($themeA);
    $registry->register($themeB);

    expect(fn () => $registry->resolveInheritanceChain('theme-a'))
        ->toThrow(ThemeInheritanceCycleException::class);

    exec('rm -rf ' . escapeshellarg($tempDir));
});

it('detects and rejects multi-node inheritance cycle (A -> B -> C -> A)', function () {
    $tempDir = sys_get_temp_dir() . '/test_inh_cycle3_' . uniqid();
    mkdir($tempDir, 0777, true);

    $registry = new ThemeRegistry();
    $themeA = new ThemeDefinition(id: 'a', name: 'A', basePath: $tempDir, parent: 'b');
    $themeB = new ThemeDefinition(id: 'b', name: 'B', basePath: $tempDir, parent: 'c');
    $themeC = new ThemeDefinition(id: 'c', name: 'C', basePath: $tempDir, parent: 'a');

    $registry->register($themeA);
    $registry->register($themeB);
    $registry->register($themeC);

    expect(fn () => $registry->resolveInheritanceChain('a'))
        ->toThrow(ThemeInheritanceCycleException::class);

    exec('rm -rf ' . escapeshellarg($tempDir));
});
