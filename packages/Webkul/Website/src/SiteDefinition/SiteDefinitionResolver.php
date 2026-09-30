<?php

namespace Webkul\Website\SiteDefinition;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;
use Webkul\Web\Contracts\WebContextContract;
use Webkul\Website\Contracts\SiteDefinitionContract;

class SiteDefinitionResolver implements SiteDefinitionContract
{
    /**
     * Forbidden local filesystem path prefixes that must never leak into public asset URLs.
     *
     * @var list<string>
     */
    private const FORBIDDEN_PATH_PREFIXES = [
        '/home/',
        '/var/',
        '/tmp/',
        '/etc/',
        '/usr/',
        '/proc/',
        '/root/',
        '/opt/',
        '/srv/',
    ];

    public function __construct(
        protected Application $app,
        protected ConfigRepository $config,
    ) {}

    /**
     * Resolve the immutable site definition for the active Web request locale
     * (or an explicitly supplied locale code).
     */
    public function current(?string $locale = null): SiteDefinition
    {
        $effectiveLocale = $locale !== null && trim($locale) !== ''
            ? trim($locale)
            : $this->resolveActiveWebLocale();

        return $this->forLocale($effectiveLocale);
    }

    /**
     * Resolve the immutable site definition for a specific locale code.
     */
    public function forLocale(string $locale): SiteDefinition
    {
        $normalizedLocale = $this->normalizeLocale($locale);
        $fallbackChain = $this->buildFallbackChain($normalizedLocale);

        $raw = (array) $this->config->get('website', []);
        $identity = is_array($raw['identity'] ?? null) ? $raw['identity'] : [];
        $contact = is_array($raw['contact'] ?? null) ? $raw['contact'] : [];
        $branding = is_array($raw['branding'] ?? null) ? $raw['branding'] : [];
        $seo = is_array($raw['seo'] ?? null) ? $raw['seo'] : [];

        $name = $this->resolveLocalizedValue($identity['name'] ?? null, $fallbackChain) ?? 'CampusHub';
        $shortName = $this->resolveLocalizedValue($identity['short_name'] ?? null, $fallbackChain) ?? $name;
        $tagline = $this->resolveLocalizedValue($identity['tagline'] ?? null, $fallbackChain);
        $description = $this->resolveLocalizedValue($identity['description'] ?? null, $fallbackChain);
        $aboutHeading = $this->resolveLocalizedValue($identity['about_heading'] ?? null, $fallbackChain);
        $aboutBody = $this->resolveLocalizedValue($identity['about_body'] ?? null, $fallbackChain);

        $email = $this->sanitizeEmail($this->resolveLocalizedValue($contact['email'] ?? null, $fallbackChain));
        $phone = $this->sanitizePhone($this->resolveLocalizedValue($contact['phone'] ?? null, $fallbackChain));
        $address = $this->resolveLocalizedValue($contact['address'] ?? null, $fallbackChain);
        $officeHours = $this->resolveLocalizedValue($contact['office_hours'] ?? null, $fallbackChain);

        $logoUrl = $this->sanitizePublicUrl($this->resolveLocalizedValue($branding['logo_url'] ?? null, $fallbackChain));
        $logoAlt = $this->resolveLocalizedValue($branding['logo_alt'] ?? null, $fallbackChain) ?? $name;
        $faviconUrl = $this->sanitizePublicUrl($this->resolveLocalizedValue($branding['favicon_url'] ?? null, $fallbackChain));

        $seoSiteName = $this->resolveLocalizedValue($seo['site_name'] ?? null, $fallbackChain) ?? $shortName;
        $seoDefaultTitle = $this->resolveLocalizedValue($seo['default_title'] ?? null, $fallbackChain) ?? $name;
        $seoDefaultDescription = $this->resolveLocalizedValue($seo['default_description'] ?? null, $fallbackChain) ?? $description;
        $seoDefaultImageUrl = $this->sanitizePublicUrl($this->resolveLocalizedValue($seo['default_image_url'] ?? null, $fallbackChain));

        return new SiteDefinition(
            locale: $normalizedLocale,
            name: $name,
            shortName: $shortName,
            tagline: $tagline,
            description: $description,
            aboutHeading: $aboutHeading,
            aboutBody: $aboutBody,
            email: $email,
            phone: $phone,
            address: $address,
            officeHours: $officeHours,
            logoUrl: $logoUrl,
            logoAlt: $logoAlt,
            faviconUrl: $faviconUrl,
            seoSiteName: $seoSiteName,
            seoDefaultTitle: $seoDefaultTitle,
            seoDefaultDescription: $seoDefaultDescription,
            seoDefaultImageUrl: $seoDefaultImageUrl,
        );
    }

    /**
     * Resolve the active public Web locale from WebContextContract dynamically per call.
     */
    protected function resolveActiveWebLocale(): string
    {
        if ($this->app->bound(WebContextContract::class)) {
            $contextLocale = trim($this->app->make(WebContextContract::class)->locale());

            if ($contextLocale !== '') {
                return $contextLocale;
            }
        }

        $appLocale = trim((string) $this->app->getLocale());

        if ($appLocale !== '') {
            return $appLocale;
        }

        return $this->resolveFallbackLocale();
    }

    /**
     * Normalize a locale code without performing database queries.
     */
    protected function normalizeLocale(string $locale): string
    {
        $trimmed = trim(str_replace('-', '_', $locale));

        if ($trimmed === '') {
            return $this->resolveFallbackLocale();
        }

        if (str_contains($trimmed, '_')) {
            [$lang, $region] = explode('_', $trimmed, 2);

            return strtolower($lang).'_'.strtoupper($region);
        }

        return strtolower($trimmed);
    }

    /**
     * Build the deterministic in-memory locale fallback chain:
     * requested locale -> configured app.fallback_locale -> 'en'.
     *
     * @return list<string>
     */
    protected function buildFallbackChain(string $requestedLocale): array
    {
        $fallback = $this->normalizeLocale($this->resolveFallbackLocale());

        return array_values(array_unique(array_filter([
            $requestedLocale,
            $fallback,
            'en',
        ], fn (string $code): bool => $code !== '')));
    }

    protected function resolveFallbackLocale(): string
    {
        $configured = trim((string) $this->config->get('app.fallback_locale', 'en'));

        return $configured !== '' ? $configured : 'en';
    }

    /**
     * Resolve a string value from either a scalar string or a locale-keyed map.
     *
     * @param   list<string>  $fallbackChain
     */
    protected function resolveLocalizedValue(mixed $value, array $fallbackChain): ?string
    {
        if (is_string($value)) {
            $trimmed = trim($value);

            return $trimmed !== '' ? $trimmed : null;
        }

        if (! is_array($value)) {
            return null;
        }

        foreach ($fallbackChain as $locale) {
            $candidate = $value[$locale] ?? null;

            if (is_string($candidate)) {
                $trimmed = trim($candidate);

                if ($trimmed !== '') {
                    return $trimmed;
                }
            }
        }

        return null;
    }

    /**
     * Validate and normalize a public email address.
     */
    protected function sanitizeEmail(?string $email): ?string
    {
        if ($email === null) {
            return null;
        }

        $trimmed = trim($email);

        if ($trimmed === '' || filter_var($trimmed, FILTER_VALIDATE_EMAIL) === false) {
            return null;
        }

        return $trimmed;
    }

    /**
     * Validate and normalize a public phone number string.
     */
    protected function sanitizePhone(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $trimmed = trim($phone);

        if ($trimmed === '' || preg_match('/^[0-9+\-() .\x{200E}\x{200F}]+$/u', $trimmed) !== 1) {
            return null;
        }

        return $trimmed;
    }

    /**
     * Validate and normalize a public URL or relative public asset path.
     * Rejects unsafe schemes (javascript:, data:, file:, etc.), protocol-relative URLs,
     * path traversal sequences, and local filesystem paths.
     */
    protected function sanitizePublicUrl(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }

        $trimmed = trim($url);

        if ($trimmed === '' || str_contains($trimmed, '..') || str_contains($trimmed, '\\')) {
            return null;
        }

        // Relative public path starting with a single slash
        if (str_starts_with($trimmed, '/')) {
            if (str_starts_with($trimmed, '//')) {
                return null;
            }

            $lower = strtolower($trimmed);
            foreach (self::FORBIDDEN_PATH_PREFIXES as $prefix) {
                if (str_starts_with($lower, $prefix)) {
                    return null;
                }
            }

            return $trimmed;
        }

        // Windows drive path check (e.g. C:/...)
        if (preg_match('/^[a-zA-Z]:/', $trimmed) === 1) {
            return null;
        }

        if (filter_var($trimmed, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        $scheme = strtolower((string) parse_url($trimmed, PHP_URL_SCHEME));

        if (! in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        return $trimmed;
    }
}
