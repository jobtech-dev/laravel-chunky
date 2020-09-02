<?php

namespace Jobtech\LaravelChunky\Tests\Unit\Concerns;

use Illuminate\Console\OutputStyle;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Jobtech\LaravelChunky\Events\ChunkDeleted;
use Jobtech\LaravelChunky\Tests\TestCase;

class ChunksHelpersTest extends TestCase
{
    /**
     * @var \Jobtech\LaravelChunky\ChunksManager
     */
    private $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = $this->app->make('chunky');
    }

    /** @test */
    public function manager_doesnt_create_progress_bar()
    {
        $progress_bar = $this->manager->hasProgressBar(null, 10);

        $this->assertNull($progress_bar);
    }

    /** @test */
    public function manager_creates_progress_bar()
    {
        $output = $this->mock(OutputStyle::class, function ($mock) {
            $mock->shouldReceive('createProgressBar')
                ->once()
                ->with(10)
                ->andReturn(null);
        });

        $result = $this->manager->hasProgressBar($output, 10);

        $this->assertNull($result);
    }

    /** @test */
    public function manager_checks_if_chunks_folder_exists()
    {
        $this->assertFalse($this->manager->chunksFolderExists('foo'));
        $this->assertFalse($this->manager->chunksFolderExists('chunks/foo'));

        $this->manager->chunksFilesystem()->makeDirectory('chunks/foo');

        $this->assertTrue($this->manager->chunksFolderExists('foo'));
        $this->assertTrue($this->manager->chunksFolderExists('chunks/foo'));
    }

    /** @test */
    public function manager_deletes_chunks()
    {
        Event::fake();

        $fake_0 = UploadedFile::fake()->create('foo.txt', 2000);
        $fake_1 = UploadedFile::fake()->create('foo.txt', 2000);

        $this->manager->addChunk($fake_0, 0, 'foo');
        $this->manager->addChunk($fake_1, 1, 'foo');

        Storage::assertExists('chunks/foo/0_foo.txt');
        Storage::assertExists('chunks/foo/1_foo.txt');

        $this->manager->deleteChunks('chunks/foo');

        Storage::assertMissing('chunks/foo/0_foo.txt');
        Storage::assertMissing('chunks/foo/1_foo.txt');
        Storage::assertMissing('chunks/foo');
        Event::assertDispatched(ChunkDeleted::class);
    }

    /** @test */
    public function manager_delete_chunks_returns_false_on_unexisting_folder()
    {
        $this->assertFalse($this->manager->deleteChunks('foo'));
    }

    /** @test */
    public function manager_deletes_all_chunks()
    {
        Event::fake();
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

        $this->manager->deleteAllChunks();

        Storage::assertMissing('foo/0_foo.txt');
        Storage::assertMissing('foo/1_foo.txt');
        Storage::assertMissing('bar/0_foo.txt');
        Storage::assertMissing('bar/1_foo.txt');
        Storage::assertMissing('foo');
        Storage::assertMissing('bar');
        Event::assertDispatched(ChunkDeleted::class);
    }
}
