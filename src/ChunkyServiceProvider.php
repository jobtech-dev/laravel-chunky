<?php

namespace Jobtech\LaravelChunky;

use Illuminate\Contracts\Container\Container;
use Illuminate\Foundation\Application as LaravelApplication;
use Illuminate\Support\ServiceProvider;
use Jobtech\LaravelChunky\Commands\ClearChunks;
use Jobtech\LaravelChunky\Contracts\ChunkyManager as ChunkyManagerContract;
use Jobtech\LaravelChunky\Support\ChunksFilesystem;
use Jobtech\LaravelChunky\Support\MergeFilesystem;
use Laravel\Lumen\Application as LumenApplication;

class ChunkyServiceProvider extends ServiceProvider
{
    /**
     * Boot the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->setupConfig();
    }

    /**
     * Register any application services.
     *
     * @return void
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
