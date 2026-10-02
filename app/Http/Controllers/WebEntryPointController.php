<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class WebEntryPointController extends Controller
{
    /**
     * Handle incoming request to root web entry point.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $defaultPackage = $this->resolveConfiguredDefaultPackage();

        if ($defaultPackage === null) {
            return view('web.fallback');
        }

        // Validate package identifier format (allow alphanumeric, underscore, hyphen)
        if (preg_match('/^[a-zA-Z0-9_\-]+$/', $defaultPackage) !== 1) {
            Log::warning("[Laraseed WebEntryPoint] Invalid default web package identifier format: [{$defaultPackage}].");

            return view('web.fallback');
        }

        // Check if the configured package is actively enabled
        if (! $this->isPackageEnabled($defaultPackage)) {
            Log::warning("[Laraseed WebEntryPoint] Configured default web package [{$defaultPackage}] is not active in optional packages.");

            return view('web.fallback');
        }

        $entryRoute = $this->resolvePackageEntryRoute($defaultPackage);

        if (! $entryRoute || ! Route::has($entryRoute)) {
            Log::warning("[Laraseed WebEntryPoint] Unable to resolve a registered entry route for enabled package [{$defaultPackage}].");

            return view('web.fallback');
        }

        try {
            $targetUrl = route($entryRoute);
        } catch (\Throwable $e) {
            Log::warning("[Laraseed WebEntryPoint] Exception generating route [{$entryRoute}] for package [{$defaultPackage}]: " . $e->getMessage());

            return view('web.fallback');
        }

        // 1. External-domain validation (Open Redirect Prevention)
        $targetHost = parse_url($targetUrl, PHP_URL_HOST);
        $requestHost = $request->getHost();
        if ($targetHost !== null && strcasecmp($targetHost, $requestHost) !== 0) {
            Log::warning("[Laraseed WebEntryPoint] Disallowed external host redirect target [{$targetUrl}] for package [{$defaultPackage}].");

            return view('web.fallback');
        }

        // 2. Direct and Indirect Redirect Loop Prevention
        $targetPath = trim((string) (parse_url($targetUrl, PHP_URL_PATH) ?? ''), '/');
        $requestPath = trim((string) (parse_url($request->url(), PHP_URL_PATH) ?? ''), '/');

        if ($entryRoute === 'laraseed.web.entry' || $targetPath === $requestPath) {
            Log::warning("[Laraseed WebEntryPoint] Direct redirect loop detected for route [{$entryRoute}] resolving to root path. Serving fallback view.");

            return view('web.fallback');
        }

        return redirect()->to($targetUrl, 302);
    }

    /**
     * Resolve the configured default package identifier with strict deterministic precedence.
     * Primary: laraseed.default_web_package
     * Fallback/Legacy: laraseed.web.default_package
     */
    protected function resolveConfiguredDefaultPackage(): ?string
    {
        $primary = config('laraseed.default_web_package');
        if (is_string($primary) && trim($primary) !== '') {
            return trim($primary);
        }

        $legacy = config('laraseed.web.default_package');
        if (is_string($legacy) && trim($legacy) !== '') {
            return trim($legacy);
        }

        return null;
    }

    /**
     * Determine if the package identifier is in the active optional packages list.
     */
    protected function isPackageEnabled(string $packageId): bool
    {
        $enabledPackages = (array) config('laraseed.optional_packages.enabled', []);

        if (in_array($packageId, $enabledPackages, true)) {
            return true;
        }

        $normalizedTarget = strtolower(str_replace('-', '_', $packageId));
        $normalizedEnabled = array_map(
            fn ($p) => strtolower(str_replace('-', '_', (string) $p)),
            $enabledPackages
        );

        return in_array($normalizedTarget, $normalizedEnabled, true);
    }

    /**
     * Resolve the named public entry route for the specified optional package.
     */
    protected function resolvePackageEntryRoute(string $packageId): ?string
    {
        if (class_exists(\Laraseed\PackageGenerator\Support\DefaultWebPackageManager::class) && app()->bound(\Laraseed\PackageGenerator\Support\DefaultWebPackageManager::class)) {
            $route = app(\Laraseed\PackageGenerator\Support\DefaultWebPackageManager::class)->resolveEntryRoute($packageId);
            if ($route !== null) {
                return $route;
            }
        }

        $packageKey = str_replace('-', '_', strtolower($packageId));

        $candidates = [
            // 1. Explicit application-level override
            config("laraseed.web.entry_routes.{$packageId}"),
            config("laraseed.web.entry_routes.{$packageKey}"),
            // 2. Package configuration overrides
            config("{$packageKey}_web.entry_route"),
            config("{$packageId}_web.entry_route"),
            config("{$packageKey}_web.navigation.home.route"),
            config("{$packageId}_web.navigation.home.route"),
            config("{$packageKey}_web.routes.home"),
            config("{$packageId}_web.routes.home"),
            // 3. Catalog capability metadata
            config("laraseed.optional_packages.catalog.{$packageId}.capabilities.web.entry_route"),
            config("laraseed.optional_packages.catalog.{$packageKey}.capabilities.web.entry_route"),
            // 4. Standard conventions
            "{$packageKey}.web.home",
            "{$packageId}.web.home",
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && $candidate !== '' && Route::has($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
