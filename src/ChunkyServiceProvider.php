<?php

namespace Jobtech\LaravelChunky;

use Illuminate\Contracts\Container\Container;
use Illuminate\Foundation\Application as LaravelApplication;
use Illuminate\Support\ServiceProvider;
use Jobtech\LaravelChunky\Commands\ClearChunks;
use Jobtech\LaravelChunky\Contracts\ChunksManager as ChunksManagerContract;
use Jobtech\LaravelChunky\Strategies\Contracts\StrategyFactory as StrategyFactoryContract;
use Jobtech\LaravelChunky\Strategies\StrategyFactory;
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
        $this->app->singleton('chunky', function (Container $app) {
            $settings = new ChunkySettings(
                $app->make('config')
            );

            return new ChunksManager(
                $app->make('filesystem'),
                $settings
            );
        });

        $this->app->singleton(StrategyFactoryContract::class, function (Container $app) {
            return new StrategyFactory(
                $app->make('config')->get('chunky.strategies')
            );
        });

        $this->app->bind('command.chunky:clear', ClearChunks::class);

        $this->commands([
            'command.chunky:clear',
        ]);

        $this->app->alias('chunky', ChunksManagerContract::class);
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
}
