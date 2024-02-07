<?php

namespace Jobtech\LaravelChunky;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Container\Container;
use Jobtech\LaravelChunky\Commands\ClearChunks;
use Jobtech\LaravelChunky\Support\TempFilesystem;
use Jobtech\LaravelChunky\Support\MergeFilesystem;
use Laravel\Lumen\Application as LumenApplication;
use Jobtech\LaravelChunky\Support\ChunksFilesystem;
use Illuminate\Foundation\Application as LaravelApplication;
use Jobtech\LaravelChunky\Contracts\ChunkyManager as ChunkyManagerContract;

class ChunkyServiceProvider extends ServiceProvider
{
    /**
     * Boot the application services.
     */
    public function boot()
    {
        $this->setupConfig();
    }

    /**
     * Register any application services.
     */
    public function register()
    {
        $this->registerBindings();
        $this->registerCommands();

        $this->app->alias(ChunkyManagerContract::class, 'chunky');
    }

    protected function setupConfig()
    {
        $source = realpath(__DIR__.'/../config/chunky.php');

        if ($this->app instanceof LaravelApplication) {
            $this->publishes([
                $source => config_path('chunky.php'),
            ], 'config');
        } elseif ($this->app instanceof LumenApplication) {
            $this->app->configure('chunky');
        }

        $this->mergeConfigFrom($source, 'chunky');
    }

    private function registerCommands()
    {
        $this->app->bind('command.chunky:clear', ClearChunks::class);

        $this->commands([
            'command.chunky:clear',
        ]);
    }

    private function registerBindings()
    {
        $this->app->singleton(TempFilesystem::class, function () {
            $config = $this->app->make('config');
            $filesystem = new TempFilesystem(app()->make(Factory::class));

            $filesystem->disk($config->get('chunky.disks.tmp.disk', $config->get('filesystems.default')));
            $filesystem->folder($config->get('chunky.disks.tmp.folder'));

            return $filesystem;
        });

        $this->app->bind(ChunksFilesystem::class, ChunksFilesystem::class);
        $this->app->bind(MergeFilesystem::class, MergeFilesystem::class);

        $this->app->singleton(ChunkySettings::class, function (Container $app) {
            return new ChunkySettings($app->make('config'));
        });

        $this->app->singleton(ChunkyManagerContract::class, function (Container $app) {
            return new ChunkyManager($app->make(ChunkySettings::class));
        });
    }
}
