<?php

namespace Webkul\Installer\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Webkul\Installer\Helpers\DatabaseManager;

class CanInstall
{
    /**
     * Handles Requests if application is already installed then redirect to dashboard else to installer.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $isInstallRoute = Str::contains($request->getPathInfo(), '/install');
        $isInstalled = $this->isAlreadyInstalled();

        if ($isInstallRoute && $isInstalled) {
            abort_if($request->ajax(), 403);

            return redirect()->route('admin.dashboard.index');
        }

        if (! $isInstallRoute && ! $isInstalled) {
            $this->ensureDatabaseIsSafeToInstall();

            return redirect()->route('installer.index');
        }

        if ($isInstallRoute && ! $isInstalled) {
            $this->ensureDatabaseIsSafeToInstall();
        }

        return $next($request);
    }

    /**
     * Refuse installation when the database cannot be proven empty.
     */
    private function ensureDatabaseIsSafeToInstall(): void
    {
        try {
            abort_unless(
                app(DatabaseManager::class)->isDatabaseEmpty(),
                409,
                'Installation refused because the database is not empty.'
            );
        } catch (HttpException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            abort(503, 'Installation state could not be verified safely.');
        }
    }

    /**
     * Check if application is already installed.
     */
    public function isAlreadyInstalled(): bool
    {
        $installedPath = storage_path('installed');

        if (file_exists($installedPath)) {
            return true;
        }

        if (! app(DatabaseManager::class)->isInstalled()) {
            return false;
        }

        touch($installedPath);

        Event::dispatch('krayin.installed');

        return true;
    }
}
