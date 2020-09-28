<?php

namespace Jobtech\LaravelChunky\Tests\Unit\Concerns;

use Illuminate\Console\OutputStyle;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Jobtech\LaravelChunky\Contracts\ChunksManager;
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

        $this->manager = $this->app->make(ChunksManager::class);
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
        $this->assertFalse($this->manager->validFolder('unexisting'));
        $this->assertFalse($this->manager->validFolder('chunks/unexisting'));

        $this->manager->chunksFilesystem()->makeDirectory('test');

        $this->assertTrue($this->manager->validFolder('test'));
        $this->assertTrue($this->manager->validFolder('chunks/test'));
    }

    /** @test */
    public function manager_deletes_chunks()
    {
        Event::fake();

        $fake_0 = $this->createFakeUpload();
        $fake_1 = $this->createFakeUpload();

        $this->manager->addChunk($fake_0, 0, 'test');
        $this->manager->addChunk($fake_1, 1, 'test');

        Storage::assertExists('chunks/test/0_fake-file.txt');
        Storage::assertExists('chunks/test/1_fake-file.txt');

        $this->manager->deleteChunkFolder('chunks/test');

        Storage::assertMissing('chunks/test/0_fake-file.txt');
        Storage::assertMissing('chunks/test/1_fake-file.txt');
        Storage::assertMissing('chunks/test');
        Event::assertDispatched(ChunkDeleted::class);
    }

    /** @test */
    public function manager_delete_chunks_returns_true_on_unexisting_folder()
    {
        $this->assertTrue($this->manager->deleteChunkFolder('unexisting'));
    }

    /** @test */
    public function manager_deletes_all_chunks()
    {
        Event::fake();
        $fake = $this->createFakeUpload();

        $this->manager->addChunk($fake, 0, 'test_1');
        $this->manager->addChunk($fake, 1, 'test_1');
        $this->manager->addChunk($fake, 0, 'test_2');
        $this->manager->addChunk($fake, 1, 'test_2');

        Storage::assertExists('chunks/test_1/0_fake-file.txt');
        Storage::assertExists('chunks/test_1/1_fake-file.txt');
        Storage::assertExists('chunks/test_2/0_fake-file.txt');
        Storage::assertExists('chunks/test_2/1_fake-file.txt');

        $this->manager->deleteAllChunks();

        Storage::assertMissing('test_1/0_fake-file.txt');
        Storage::assertMissing('test_1/1_fake-file.txt');
        Storage::assertMissing('test_2/0_fake-file.txt');
        Storage::assertMissing('test_2/1_fake-file.txt');
        Storage::assertMissing('test_1');
        Storage::assertMissing('test_2');
        Event::assertDispatched(ChunkDeleted::class);
    }
}
