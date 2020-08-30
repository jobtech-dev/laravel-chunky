<?php

namespace Jobtech\LaravelChunky\Tests\Unit\Commands;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Jobtech\LaravelChunky\ChunksManager;
use Jobtech\LaravelChunky\Tests\TestCase;

class ClearChunksTest extends TestCase
{
    /**
     * @var mixed
     */
    private $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = $this->app->make('chunky');
    }

    /** @test */
    public function command_clears_chunks()
    {
        $fake_0 = UploadedFile::fake()->create('foo.txt', 2000);
        $fake_1 = UploadedFile::fake()->create('foo.txt', 2000);

        $this->manager->addChunk($fake_0, 0, 'foo');
        $this->manager->addChunk($fake_1, 1, 'foo');

        Storage::assertExists('chunks/foo/0_foo.txt');
        Storage::assertExists('chunks/foo/1_foo.txt');

        $this->artisan('chunky:clear', ['folder' => 'foo'])
            ->expectsOutput('folder chunks/foo cleared!')
            ->assertExitCode(0);

        Storage::assertMissing('foo/0_foo.txt');
        Storage::assertMissing('foo/1_foo.txt');
        Storage::assertMissing('foo');
    }

    /** @test */
    public function command_clears_all_chunks()
    {
        $fake_0 = UploadedFile::fake()->create('foo.txt', 2000);
        $fake_1 = UploadedFile::fake()->create('foo.txt', 2000);
        $fake_2 = UploadedFile::fake()->create('foo.txt', 2000);
        $fake_3 = UploadedFile::fake()->create('foo.txt', 2000);

        $this->manager->addChunk($fake_0, 0, 'foo');
        $this->manager->addChunk($fake_1, 1, 'foo');
        $this->manager->addChunk($fake_2, 0, 'bar');
        $this->manager->addChunk($fake_3, 1, 'bar');

        Storage::assertExists('chunks/foo/0_foo.txt');
        Storage::assertExists('chunks/foo/1_foo.txt');
        Storage::assertExists('chunks/bar/0_foo.txt');
        Storage::assertExists('chunks/bar/1_foo.txt');

        $this->artisan('chunky:clear')
            ->expectsOutput('Chunks folder cleared!')
            ->assertExitCode(0);

        Storage::assertMissing('foo/0_foo.txt');
        Storage::assertMissing('foo/1_foo.txt');
        Storage::assertMissing('bar/0_foo.txt');
        Storage::assertMissing('bar/1_foo.txt');
        Storage::assertMissing('foo');
        Storage::assertMissing('bar');
    }

    /** @test */
    public function command_shows_error_if_manager_delete_chunks_fail()
    {
        $mock = $this->mock(ChunksManager::class, function ($mock) {
            $mock->shouldReceive('getChunksFolder')
                ->once()
                ->andReturn('/');

            $mock->shouldReceive('deleteChunks')
                ->once()
                ->with('/foo')
                ->andReturn(false);
        });

        $this->app->bind('chunky', function () use ($mock) {
            return $mock;
        });

        $this->artisan('chunky:clear', ['folder' => 'foo'])
            ->expectsOutput('An error occurred while deleting folder /foo')
            ->assertExitCode(0);
    }
}
