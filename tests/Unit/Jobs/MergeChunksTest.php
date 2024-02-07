<?php

namespace Jobtech\LaravelChunky\Tests\Unit\Jobs;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Jobtech\LaravelChunky\Tests\TestCase;
use Jobtech\LaravelChunky\Jobs\MergeChunks;
use Jobtech\LaravelChunky\Events\ChunkDeleted;
use Jobtech\LaravelChunky\Events\ChunksMerged;
use Jobtech\LaravelChunky\Contracts\ChunkyManager;
use Jobtech\LaravelChunky\Exceptions\ChunksIntegrityException;

/**
 * @internal
 */
class MergeChunksTest extends TestCase
{
    /**
     * @var \Jobtech\LaravelChunky\ChunkyManager
     */
    private $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = $this->app->make(ChunkyManager::class);
    }

    /** @test */
    public function jobThrowsExceptionIfIntegrityDoesntMatch()
    {
        $this->manager->chunksFilesystem()->makeDirectory('chunks/foo');

        $job = new MergeChunks(
            'chunks/foo',
            'merged.txt',
            1000,
            1000
        );

        $this->expectException(ChunksIntegrityException::class);

        $job->handle();
    }

    /** @test */
    public function jobHandlesMerge()
    {
        Event::fake();

        $chunk_size_0 = $this->manager->chunksFilesystem()->filesystem()->size('chunks/foo/0_chunk.txt');
        $chunk_size_1 = $this->manager->chunksFilesystem()->filesystem()->size('chunks/foo/1_chunk.txt');
        $chunk_size_2 = $this->manager->chunksFilesystem()->filesystem()->size('chunks/foo/2_chunk.txt');
        $total_size = $chunk_size_0 + $chunk_size_1 + $chunk_size_2;

        $job = new MergeChunks(
            'chunks/foo',
            'merged.txt',
            $chunk_size_0,
            $total_size
        );

        $job->handle();

        Storage::assertMissing('chunks/foo/0_foo.txt');
        Storage::assertMissing('chunks/foo/1_foo.txt');
        Storage::assertExists('merged.txt');
        Event::assertDispatched(ChunkDeleted::class);
        Event::assertDispatched(ChunksMerged::class);
    }
}
