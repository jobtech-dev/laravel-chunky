<?php

namespace Jobtech\LaravelChunky\Tests\Unit\Jobs;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Jobtech\LaravelChunky\Events\ChunkDeleted;
use Jobtech\LaravelChunky\Events\ChunksMerged;
use Jobtech\LaravelChunky\Exceptions\ChunksIntegrityException;
use Jobtech\LaravelChunky\Http\Requests\AddChunkRequest;
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

        $this->manager = $this->app->make('chunky');
    }

    /** @test */
    public function job_throws_exception_if_integrity_doesnt_match()
    {
        $this->manager->chunksFilesystem()->makeDirectory('chunks/foo');

        $upload = UploadedFile::fake()->create('foo.txt', 1000);

        $job = new MergeChunks(
            'chunks/foo',
            'merged.txt',
            $upload->getMimeType(),
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
        $this->manager->chunksFilesystem()->write('foo.txt', 'Hello World');
        $this->manager->chunksFilesystem()->write('chunks/foo/0_foo.txt', 'Hello ');
        $this->manager->chunksFilesystem()->write('chunks/foo/1_foo.txt', 'World');

        $chunk_size = $this->manager->chunksFilesystem()->size('chunks/foo/1_foo.txt');
        $total_size = $this->manager->chunksFilesystem()->size('foo.txt');
        $upload = UploadedFile::fake()->create('foo.txt', $chunk_size);

        $job = new MergeChunks(
            'chunks/foo',
            'merged.txt',
            $upload->getMimeType(),
            $chunk_size,
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
