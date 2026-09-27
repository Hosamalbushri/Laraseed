<?php

namespace Webkul\Installer\Helpers;

use Exception;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Webkul\Installer\Database\Seeders\DatabaseSeeder as KrayinDatabaseSeeder;

class DatabaseManager
{
    /**
     * Check Database Connection.
     */
    public function isInstalled()
    {
        if (! file_exists(base_path('.env'))) {
            return false;
        }

        try {
            DB::connection()->getPDO();

            $isConnected = (bool) DB::connection()->getDatabaseName();

            if (! $isConnected) {
                return false;
            }

            $hasUserTable = Schema::hasTable('users');

            if (! $hasUserTable) {
                return false;
            }

            $userCount = DB::table('users')->count();

            if (! $userCount) {
                return false;
            }

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Determine whether the configured database has no application tables.
     *
     * Connection failures are deliberately allowed to bubble up so callers can
     * fail closed instead of treating an unknown database as safe to install.
     */
    public function isDatabaseEmpty(): bool
    {
        DB::connection()->getPDO();

        return Schema::getTableListing() === [];
    }

    /**
     * Migrate a verified-empty database without exposing a destructive reset.
     *
     * @return void|string
     */
    public function migration()
    {
        abort_if(app()->environment('production'), 403, 'Web installation is unavailable in production.');

        try {
            abort_unless($this->isDatabaseEmpty(), 409, 'Installation refused because the database is not empty.');

            Artisan::call('migrate', ['--force' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Tables is migrated successfully.',
            ]);
        } catch (HttpException $e) {
            throw $e;
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Seed the database.
     *
     * @return void|string
     */
    public function seeder($data)
    {
        try {
            app(KrayinDatabaseSeeder::class)->run([
                'default_locale' => $data['parameter']['default_locales'],
                'default_currency' => $data['parameter']['default_currency'],
            ]);

            $this->storageLink();
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * Storage Link.
     */
    private function storageLink()
    {
        Artisan::call('storage:link');
    }

    /**
     * Generate New Application Key
     */
    public function generateKey()
    {
        try {
            Artisan::call('key:generate');
        } catch (Exception $e) {
        }
    }
}
