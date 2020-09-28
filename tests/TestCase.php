<?php

namespace Jobtech\LaravelChunky\Tests;

use Dotenv\Dotenv;
use File;
use Illuminate\Http\UploadedFile;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    private function setupS3($app): void
    {
        config()->set('filesystems.disks.s3_disk', [
            'driver' => 's3',
            'key' => getenv('AWS_ACCESS_KEY_ID'),
            'secret' => getenv('AWS_SECRET_ACCESS_KEY'),
            'region' => getenv('AWS_DEFAULT_REGION'),
            'bucket' => getenv('AWS_BUCKET'),
        ]);
    }

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
            \Jobtech\LaravelChunky\ChunkyServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        if (File::isDirectory(__DIR__.'/tmp')) {
            File::deleteDirectory(__DIR__.'/tmp');
        }
        File::makeDirectory(__DIR__.'/tmp');

        if (! File::isDirectory(__DIR__.'/tmp/resources')) {
            File::copyDirectory(__DIR__.'/resources', __DIR__.'/tmp');
        }

        config()->set('filesystems.disks.local', [
            'driver' => 'local',
            'root'   => __DIR__.'/tmp',
        ]);

        config()->set('app.key', 'base64:+XjCO29J0UznAKkeY5K+Tfd/3WWUiRUsefaxScOF3fM=');

        $this->setupS3($app);
    }

    protected function loadEnvironmentVariables()
    {
        if (! file_exists(__DIR__.'/../.env')) {
            return;
        }

        $dotEnv = Dotenv::createImmutable(__DIR__.'/..');

        $dotEnv->load();
    }

    /**
     * @return \Illuminate\Http\UploadedFile
     */
    public function createFakeUpload(): UploadedFile
    {
        return new UploadedFile(__DIR__.'/tmp/upload/fake_file.txt', 'fake_file.txt');
    }

    public function canTestS3()
    {
        return ! empty(getenv('AWS_ACCESS_KEY_ID'));
    }
}
