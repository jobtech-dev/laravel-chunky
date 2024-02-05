<?php

namespace Jobtech\LaravelChunky\Tests\Unit\Commands;

use Illuminate\Support\Facades\Storage;
use Jobtech\LaravelChunky\ChunkyManager;
use Jobtech\LaravelChunky\ChunkySettings;
use Jobtech\LaravelChunky\Tests\TestCase;

/**
 * @internal
 */
class ClearChunksTest extends TestCase
{
    /**
     * @var mixed
     */
    private $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = new ChunkyManager(
            new ChunkySettings($this->app->make('config'))
        );
    }

    /** @test */
    public function commandClearsChunks()
    {
        $fake_0 = $this->createFakeUpload();
        $fake_1 = $this->createFakeUpload();

        $this->manager->addChunk($fake_0, 0, 'test');
        $this->manager->addChunk($fake_1, 1, 'test');

        Storage::assertExists('chunks/test/0_fake-file.txt');
        Storage::assertExists('chunks/test/1_fake-file.txt');

        $this->artisan('chunky:clear', ['folder' => 'test'])
            ->expectsOutput('folder chunks/test has been deleted!')
            ->assertExitCode(0);

        Storage::assertMissing('test/0_fake-file.txt');
        Storage::assertMissing('test/1_fake-file.txt');
        Storage::assertMissing('test');
    }

    /** @test */
    public function commandClearsAllChunks()
    {
        $fake = $this->createFakeUpload();

        $this->manager->addChunk($fake, 0, 'test_1');
        $this->manager->addChunk($fake, 1, 'test_1');
        $this->manager->addChunk($fake, 0, 'test_2');
        $this->manager->addChunk($fake, 1, 'test_2');

        Storage::assertExists('chunks/test_1/0_fake-file.txt');
        Storage::assertExists('chunks/test_1/1_fake-file.txt');
        Storage::assertExists('chunks/test_2/0_fake-file.txt');
        Storage::assertExists('chunks/test_2/1_fake-file.txt');

        $this->artisan('chunky:clear')
            ->expectsOutput('Chunks folders have been deleted!')
            ->assertExitCode(0);

        Storage::assertMissing('test_1/0_fake-file.txt');
        Storage::assertMissing('test_1/1_fake-file.txt');
        Storage::assertMissing('test_2/0_fake-file.txt');
        Storage::assertMissing('test_2/1_fake-file.txt');
        Storage::assertMissing('test_1');
        Storage::assertMissing('test_2');
    }
}
