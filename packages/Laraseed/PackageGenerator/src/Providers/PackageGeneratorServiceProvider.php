<?php

namespace Laraseed\PackageGenerator\Providers;

use Illuminate\Support\ServiceProvider;
use Laraseed\PackageGenerator\Console\Commands\AdminMakeCommand;
use Laraseed\PackageGenerator\Console\Commands\CommandMakeCommand;
use Laraseed\PackageGenerator\Console\Commands\ContractMakeCommand;
use Laraseed\PackageGenerator\Console\Commands\ControllerMakeCommand;
use Laraseed\PackageGenerator\Console\Commands\DataGridMakeCommand;
use Laraseed\PackageGenerator\Console\Commands\EventMakeCommand;
use Laraseed\PackageGenerator\Console\Commands\ListenerMakeCommand;
use Laraseed\PackageGenerator\Console\Commands\MigrationMakeCommand;
use Laraseed\PackageGenerator\Console\Commands\ModelMakeCommand;
use Laraseed\PackageGenerator\Console\Commands\ModuleProviderMakeCommand;
use Laraseed\PackageGenerator\Console\Commands\PackageMakeCommand;
use Laraseed\PackageGenerator\Console\Commands\ProviderMakeCommand;
use Laraseed\PackageGenerator\Console\Commands\RepositoryMakeCommand;
use Laraseed\PackageGenerator\Console\Commands\RequestMakeCommand;
use Laraseed\PackageGenerator\Console\Commands\RouteMakeCommand;
use Laraseed\PackageGenerator\Console\Commands\SeederMakeCommand;
use Laraseed\PackageGenerator\Generators\FilesystemWriter;
use Laraseed\PackageGenerator\Generators\PackageGenerator;
use Laraseed\PackageGenerator\Generators\StubRenderer;

class PackageGeneratorServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(PackageGenerator::class, function ($app) {
            return new PackageGenerator(
                new StubRenderer,
                new FilesystemWriter,
                $app->basePath()
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                PackageMakeCommand::class,
                ModelMakeCommand::class,
                ContractMakeCommand::class,
                MigrationMakeCommand::class,
                RepositoryMakeCommand::class,
                RequestMakeCommand::class,
                ControllerMakeCommand::class,
                RouteMakeCommand::class,
                ProviderMakeCommand::class,
                ModuleProviderMakeCommand::class,
                EventMakeCommand::class,
                ListenerMakeCommand::class,
                CommandMakeCommand::class,
                SeederMakeCommand::class,
                DataGridMakeCommand::class,
                AdminMakeCommand::class,
            ]);
        }
    }
}
