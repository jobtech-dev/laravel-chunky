<?php

namespace Jobtech\LaravelChunky\Tests;

use Dotenv\Dotenv;
use File;
use Jobtech\LaravelChunky\ChunkyServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->loadEnvironmentVariables();

        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function getPackageProviders($app)
    {
        return [
            \ProtoneMedia\LaravelFFMpeg\Support\ServiceProvider::class,
            ChunkyServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        if (File::isDirectory(__DIR__.'/tmp')) {
            File::deleteDirectory(__DIR__.'/tmp');
        }
        File::makeDirectory(__DIR__.'/tmp');

        if (!File::isDirectory(__DIR__.'/tmp/resources')) {
            File::copyDirectory(__DIR__.'/resources', __DIR__.'/tmp/resources');
        }

        config()->set('filesystems.disks.local', [
            'driver' => 'local',
            'root'   => __DIR__.'/tmp',
        ]);
    }

    protected function loadEnvironmentVariables()
    {
        if (! file_exists(__DIR__.'/../.env')) {
            return;
        }

        $dotEnv = Dotenv::createImmutable(__DIR__.'/..');

        $dotEnv->load();
    }
}
