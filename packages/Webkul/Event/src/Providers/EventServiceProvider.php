<?php

namespace Webkul\Event\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event as LaravelEvent;
use Illuminate\Support\ServiceProvider;
use Webkul\Admin\Helpers\Dashboard;
use Webkul\Admin\Helpers\DashboardStatsRegistry;
use Webkul\Admin\Helpers\MegaSearch;
use Webkul\Core\ViewRenderEventManager;
use Webkul\Event\Listeners\RenderStudentSubscriptions;
use Webkul\Event\Models\EventProxy;
use Webkul\Event\Services\EventDashboardService;
use Webkul\Event\Services\EventSubscriptionService;
use Webkul\Event\Services\EventWriteService;
use Webkul\Student\Models\Student;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'event');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'event');

        $this->loadRoutesFrom(__DIR__.'/../Routes/admin-routes.php');

        require __DIR__.'/../Routes/breadcrumbs.php';

        $this->registerDashboardContributions();
        $this->registerViewContributions();
        $this->registerStudentExtensions();

        app(MegaSearch::class)->register(
            'events',
            trans('event::app.events.title'),
            'admin.events.search',
            10,
        );
    }

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(EventSubscriptionService::class);
        $this->app->singleton(EventDashboardService::class);
        $this->app->singleton(EventWriteService::class);

        $this->registerConfig();
    }

    /**
     * Register package config.
     */
    protected function registerConfig(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/acl.php', 'acl');

        $this->mergeConfigFrom(__DIR__.'/../Config/menu.php', 'menu.admin');

        $this->registerCoreConfigContributions();
    }

    protected function registerCoreConfigContributions(): void
    {
        $contributions = require __DIR__.'/../Config/core_config.php';
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

    protected function registerDashboardContributions(): void
    {
        $registry = app(DashboardStatsRegistry::class);
        $dashboard = app(Dashboard::class);
        $eventDashboard = app(EventDashboardService::class);

        $registry->register('events-students-over-all', function () use ($dashboard, $eventDashboard): array {
            [$startDate, $endDate] = $dashboard->getDateBounds();
            [$previousStart, $previousEnd] = $dashboard->getPreviousDateBounds($startDate, $endDate);

            return $eventDashboard->getOverallMetrics(
                $startDate,
                $endDate,
                $previousStart,
                $previousEnd,
                $dashboard,
            );
        });

        $registry->register('student-subscriptions-over-time', fn (): array => $eventDashboard->getStudentSubscriptionsOverTime($dashboard));
        $registry->register('events-status-distribution', fn (): array => $eventDashboard->getEventsStatusDistribution());
        $registry->register('top-subscribed-events', fn (): array => $eventDashboard->getTopSubscribedEvents($dashboard));
    }

    protected function registerViewContributions(): void
    {
        $templates = [
            'admin.dashboard.index.content.left' => 'event::admin.dashboard.left',
            'admin.dashboard.index.content.right' => 'event::admin.dashboard.right',
            'admin.components.layouts.header.desktop.mega_search.results' => 'event::admin.layouts.header.mega-search-results',
            'admin.components.layouts.header.mobile.mega_search.results' => 'event::admin.layouts.header.mobile-mega-search-results',
            'admin.components.layouts.header.quick_creation' => 'event::admin.layouts.header.quick-creation-item',
        ];

        foreach ($templates as $event => $template) {
            LaravelEvent::listen($event, function (ViewRenderEventManager $manager) use ($template): void {
                $manager->addTemplate($template);
            });
        }

        LaravelEvent::listen(
            'admin.students.view.details.after',
            RenderStudentSubscriptions::class,
        );

        LaravelEvent::listen('admin.students.datagrid.query.after', function ($query): void {
            $query
                ->leftJoin('event_student', 'students.id', '=', 'event_student.student_id')
                ->addSelect(DB::raw('COUNT(event_student.event_id) as subscribed_events_count'))
                ->groupBy(
                    'students.id',
                    'students.name',
                    'students.university_card_number',
                    'students.registration_number',
                    'students.major',
                    'students.academic_level',
                    'students.created_at',
                );
        });

        LaravelEvent::listen('admin.students.datagrid.columns.after', function ($dataGrid): void {
            $dataGrid->addColumn([
                'index' => 'subscribed_events_count',
                'label' => trans('event::app.students.datagrid.subscribed-events-count'),
                'type' => 'integer',
                'filterable' => false,
                'sortable' => true,
                'searchable' => false,
            ]);
        });
    }

    protected function registerStudentExtensions(): void
    {
        Student::resolveRelationUsing('subscribedEvents', function (Student $student) {
            return $student->belongsToMany(
                EventProxy::modelClass(),
                'event_student',
                'student_id',
                'event_id',
            )->withTimestamps();
        });

        Student::deleting(function (Student $student): void {
            app(EventSubscriptionService::class)
                ->removeStudentSubscriptions((int) $student->getKey());
        });
    }
}
