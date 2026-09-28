<?php

namespace Webkul\Theme\Definitions;

use Webkul\Theme\Exceptions\InvalidThemeManifestException;
use Webkul\Theme\Exceptions\InvalidThemePathException;

readonly class ThemeDefinition
{
    /**
     * Regex pattern for valid theme IDs (kebab-case / lowercase alphanumeric with hyphens/underscores).
     */
    public const ID_PATTERN = '/^[a-z0-9][a-z0-9\-_]*$/';

    /**
     * Normalized theme ID.
     */
    public string $id;

    /**
     * Parent theme ID, if any.
     */
    public ?string $parent;

    /**
     * Human-readable theme name.
     */
    public string $name;

    /**
     * Absolute base path to the theme directory.
     */
    public string $basePath;

    /**
     * Absolute path to the views directory.
     */
    public string $viewsPath;

    /**
     * Absolute path to the assets directory, or null if theme has no assets.
     */
    public ?string $assetsPath;

    /**
     * Theme version string.
     */
    public string $version;

    /**
     * Additional theme metadata.
     *
     * @var array<string, mixed>
     */
    public array $extra;

    /**
     * Create a new validated ThemeDefinition instance.
     */
    public function __construct(
        string $id,
        string $name,
        string $basePath,
        ?string $parent = null,
        ?string $viewsPath = null,
        ?string $assetsPath = null,
        string $version = '1.0.0',
        array $extra = []
    ) {
        $normalizedId = strtolower(trim($id));

        if (! preg_match(self::ID_PATTERN, $normalizedId)) {
            throw new InvalidThemeManifestException(
                "Theme ID [{$id}] is invalid. Must match regex pattern: " . self::ID_PATTERN
            );
        }

        if (trim($name) === '') {
            throw new InvalidThemeManifestException("Theme [{$normalizedId}] must provide a non-empty name.");
        }

        $normalizedParent = $parent !== null ? strtolower(trim($parent)) : null;

        if ($normalizedParent !== null && ! preg_match(self::ID_PATTERN, $normalizedParent)) {
            throw new InvalidThemeManifestException(
                "Parent theme ID [{$parent}] for theme [{$normalizedId}] is invalid."
            );
        }

        $realBasePath = realpath($basePath);

        if ($realBasePath === false || ! is_dir($realBasePath)) {
            throw new InvalidThemePathException(
                "Theme base path [{$basePath}] does not exist or is not a directory for theme [{$normalizedId}]."
            );
        }

        $resolvedViewsPath = $viewsPath ?? ($realBasePath . '/views');
        $resolvedAssetsPath = $assetsPath ?? ($realBasePath . '/assets');

        $realViewsPath = realpath($resolvedViewsPath);
        if ($realViewsPath === false || ! is_dir($realViewsPath)) {
            $realViewsPath = $resolvedViewsPath;
        }

        $realAssetsPath = null;
        if (is_dir($resolvedAssetsPath)) {
            $realAssetsPath = realpath($resolvedAssetsPath) ?: $resolvedAssetsPath;
        }

        $this->id = $normalizedId;
        $this->name = trim($name);
        $this->parent = $normalizedParent !== '' ? $normalizedParent : null;
        $this->basePath = $realBasePath;
        $this->viewsPath = $realViewsPath;
        $this->assetsPath = $realAssetsPath;
        $this->version = trim($version) !== '' ? trim($version) : '1.0.0';
        $this->extra = $extra;
    }

    /**
     * Create a ThemeDefinition from a manifest array and directory path.
     *
     * @param  array<string, mixed>  $manifest
     */
    public static function fromArray(array $manifest, string $basePath): self
    {
        if (empty($manifest['id']) || ! is_string($manifest['id'])) {
            throw new InvalidThemeManifestException('Theme manifest must contain a non-empty string "id".');
        }

        if (empty($manifest['name']) || ! is_string($manifest['name'])) {
            throw new InvalidThemeManifestException('Theme manifest must contain a non-empty string "name".');
        }

        $viewsPath = isset($manifest['views_path']) && is_string($manifest['views_path'])
            ? (str_starts_with($manifest['views_path'], '/') ? $manifest['views_path'] : $basePath . '/' . ltrim($manifest['views_path'], '/'))
            : null;

        $assetsPath = isset($manifest['assets_path']) && is_string($manifest['assets_path'])
            ? (str_starts_with($manifest['assets_path'], '/') ? $manifest['assets_path'] : $basePath . '/' . ltrim($manifest['assets_path'], '/'))
            : null;

        return new self(
            id: $manifest['id'],
            name: $manifest['name'],
            basePath: $basePath,
            parent: isset($manifest['parent']) && is_string($manifest['parent']) ? $manifest['parent'] : null,
            viewsPath: $viewsPath,
            assetsPath: $assetsPath,
            version: isset($manifest['version']) && is_string($manifest['version']) ? $manifest['version'] : '1.0.0',
            extra: isset($manifest['extra']) && is_array($manifest['extra']) ? $manifest['extra'] : []
        );
    }

    /**
     * Create a ThemeDefinition from a theme.json manifest file path.
     */
    public static function fromManifestFile(string $manifestPath): self
    {
        if (! file_exists($manifestPath) || ! is_readable($manifestPath)) {
            throw new InvalidThemePathException("Theme manifest file [{$manifestPath}] does not exist or is not readable.");
        }

        $content = file_get_contents($manifestPath);
        if ($content === false || trim($content) === '') {
            throw new InvalidThemeManifestException("Theme manifest file [{$manifestPath}] is empty.");
        }

        $data = json_decode($content, true);
        if (! is_array($data)) {
            throw new InvalidThemeManifestException("Theme manifest file [{$manifestPath}] contains invalid JSON.");
        }

        return self::fromArray($data, dirname($manifestPath));
    }
}
