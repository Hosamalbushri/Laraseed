<?php

namespace Webkul\Student\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event as LaravelEvent;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use LogicException;
use Webkul\Admin\Helpers\MegaSearch;
use Webkul\Core\Contracts\AuthenticationRedirectResolver;
use Webkul\Core\ViewRenderEventManager;
use Webkul\Student\Services\Contracts\UniversityStudentApiContract;
use Webkul\Student\Services\FakeUniversityStudentApiClient;
use Webkul\Student\Services\StudentAdminService;
use Webkul\Student\Services\UniversityStudentApiClient;

class StudentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->registerAuthentication();

        $this->app->singleton(StudentAdminService::class);

        $this->registerConfig();

        $this->app->singleton(UniversityStudentApiContract::class, function ($app) {
            $fakeRequested = (bool) $app['config']->get('student.university.fake', false);

            if ($app->environment('production') && $fakeRequested) {
                throw new LogicException('Fake university authentication is prohibited in production.');
            }

            if ($fakeRequested) {
                return new FakeUniversityStudentApiClient;
            }

            return new UniversityStudentApiClient;
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        app(AuthenticationRedirectResolver::class)->register(
            'student',
            fn (Request $request): bool => $request->is('student/*'),
            fn (): string => route('student.login'),
            100,
        );

        RateLimiter::for('student-login', function (Request $request) {
            return Limit::perMinute(5)->by(
                sha1($request->ip().'|'.(string) $request->input('university_card_number'))
            );
        });

        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'student');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'student');

        $this->loadRoutesFrom(__DIR__.'/../Routes/admin-routes.php');

        Route::middleware(['web', 'admin_locale', PreventRequestsDuringMaintenance::class])
            ->group(__DIR__.'/../Routes/web.php');

        require __DIR__.'/../Routes/breadcrumbs.php';

        $this->registerViewContributions();

        app(MegaSearch::class)->register(
            'students',
            trans('student::app.students.title'),
            'admin.students.search',
            20,
        );
    }

    /**
     * Register package config.
     */
    protected function registerConfig(): void
    {
        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/student.php',
            'student'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/acl.php',
            'acl'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/menu.php',
            'menu.admin'
        );

        $this->registerCoreConfigContributions();
    }

    protected function registerAuthentication(): void
    {
        $authentication = require dirname(__DIR__).'/Config/auth.php';

        config([
            'auth.guards' => [
                ...config('auth.guards', []),
                ...$authentication['guards'],
            ],
            'auth.providers' => [
                ...config('auth.providers', []),
                ...$authentication['providers'],
            ],
        ]);
    }

    protected function registerCoreConfigContributions(): void
    {
        $contributions = require dirname(__DIR__).'/Config/core_config.php';
        $coreConfig = config('core_config', []);

        foreach ($contributions['fields'] as $targetKey => $fields) {
            foreach ($coreConfig as &$item) {
                if (($item['key'] ?? null) === $targetKey) {
                    $item['fields'] = [...($item['fields'] ?? []), ...$fields];

                    break;
                }
            }

            unset($item);
        }

        config(['core_config' => [...$coreConfig, ...$contributions['items']]]);
    }

    protected function registerViewContributions(): void
    {
        $templates = [
            'admin.components.layouts.header.desktop.mega_search.results' => 'student::admin.layouts.header.desktop-mega-search-results',
            'admin.components.layouts.header.mobile.mega_search.results' => 'student::admin.layouts.header.mobile-mega-search-results',
            'admin.components.layouts.header.quick_creation' => 'student::admin.layouts.header.quick-creation-item',
        ];

        foreach ($templates as $event => $template) {
            LaravelEvent::listen($event, function (ViewRenderEventManager $manager) use ($template): void {
                $manager->addTemplate($template);
            });
        }
    }
}
