<?php

namespace Jobtech\LaravelChunky\Tests;

use Jobtech\LaravelChunky\ChunkyServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use File;

abstract class TestCase extends Orchestra
{
    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getPackageProviders($app)
    {
        return [
            \ProtoneMedia\LaravelFFMpeg\Support\ServiceProvider::class,
            ChunkyServiceProvider::class
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        if (File::isDirectory(__DIR__.'/tmp')) {
            File::deleteDirectory(__DIR__.'/tmp');
        }
        File::makeDirectory(__DIR__.'/tmp');

        if(!File::isDirectory(__DIR__.'/tmp/resources')) {
            File::copyDirectory(__DIR__.'/resources', __DIR__.'/tmp/resources');
        }

        config()->set('filesystems.disks.local', [
            'driver' => 'local',
            'root' => __DIR__.'/tmp',
        ]);
    }
}