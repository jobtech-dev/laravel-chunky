<?php

namespace Jobtech\LaravelChunky\Tests\Unit\Jobs;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Jobtech\LaravelChunky\Contracts\ChunksManager;
use Jobtech\LaravelChunky\Events\ChunkDeleted;
use Jobtech\LaravelChunky\Events\ChunksMerged;
use Jobtech\LaravelChunky\Exceptions\ChunksIntegrityException;
use Jobtech\LaravelChunky\Jobs\MergeChunks;
use Jobtech\LaravelChunky\Tests\TestCase;

class MergeChunksTest extends TestCase
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
    public function job_throws_exception_if_integrity_doesnt_match()
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
    public function job_handles_merge()
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

        Storage::disk('local')->assertMissing('chunks/foo/0_foo.txt');
        Storage::disk('local')->assertMissing('chunks/foo/1_foo.txt');
        Storage::disk('local')->assertExists('merged.txt');
        Event::assertDispatched(ChunkDeleted::class);
        Event::assertDispatched(ChunksMerged::class);
    }
}
