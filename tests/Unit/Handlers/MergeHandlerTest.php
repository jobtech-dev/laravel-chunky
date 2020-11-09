<?php

namespace Jobtech\LaravelChunky\Tests\Unit\Handlers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Jobtech\LaravelChunky\ChunkyManager;
use Jobtech\LaravelChunky\ChunkySettings;
use Jobtech\LaravelChunky\Events\ChunksMerged;
use Jobtech\LaravelChunky\Exceptions\ChunksIntegrityException;
use Jobtech\LaravelChunky\Exceptions\ChunkyException;
use Jobtech\LaravelChunky\Handlers\MergeHandler;
use Jobtech\LaravelChunky\Jobs\MergeChunks;
use Jobtech\LaravelChunky\Tests\TestCase;
use Mockery;

class MergeHandlerTest extends TestCase
{
    public function handler_is_created_with_manager()
    {
        $manager = new ChunkyManager(new ChunkySettings($this->app->make('config')));
        $handler = new MergeHandler($manager);

        $this->assertEquals($manager, $handler->manager());
    }

    public function invalid_chunks_integrity_throws_exception()
    {
        $request_mock = Mockery::mock(ChunkyManager::class);
        $request_mock->shouldReceive('fileInput')
            ->once()
            ->andReturnSelf();
        $request_mock->shouldReceive('getClientOriginalName')
            ->once()
            ->andReturn('test.txt');
        $request_mock->shouldReceive('chunkSizeInput')
            ->once()
            ->andReturn(10);
        $request_mock->shouldReceive('totalSizeInput')
            ->once()
            ->andReturn(100);

        $manager_mock = Mockery::mock(ChunkyManager::class);
        $manager_mock->shouldReceive('settings')
            ->once()
            ->andReturnSelf();
        $manager_mock->shouldReceive('connection')
            ->once()
            ->andReturn('default');
        $manager_mock->shouldReceive('checkChunksIntegrity')
            ->once()
            ->with('foo', 10, 100)
            ->andReturn(false);

        $handler = new MergeHandler($manager_mock);

        $this->expectException(ChunksIntegrityException::class);

        $handler->dispatchMerge($request_mock, 'foo');
    }

    public function handler_dispatch_merge()
    {
        Queue::fake();
        $request_mock = Mockery::mock(ChunkyManager::class);
        $request_mock->shouldReceive('fileInput')
            ->once()
            ->andReturnSelf();
        $request_mock->shouldReceive('getClientOriginalName')
            ->once()
            ->andReturn('test.txt');
        $request_mock->shouldReceive('chunkSizeInput')
            ->once()
            ->andReturn(10);
        $request_mock->shouldReceive('totalSizeInput')
            ->once()
            ->andReturn(100);

        $manager_mock = Mockery::mock(ChunkyManager::class);
        $manager_mock->shouldReceive('settings')
            ->times(2)
            ->andReturnSelf();
        $manager_mock->shouldReceive('connection')
            ->once()
            ->andReturn('default');
        $manager_mock->shouldReceive('queue')
            ->once()
            ->andReturn('foo');
        $manager_mock->shouldReceive('checkChunksIntegrity')
            ->once()
            ->with('foo', 10, 100)
            ->andReturn(true);

        $handler = new MergeHandler($manager_mock);

        $handler->dispatchMerge($request_mock, 'foo');
        Queue::assertPushedOn('foo', MergeChunks::class);
    }

    /** @test */
    public function handler_throws_exception_with_invalid_chunk_folder()
    {
        $mock = Mockery::mock(ChunkyManager::class);
        $mock->shouldReceive('isValidChunksFolder')
            ->once()
            ->with('foo')
            ->andReturn(false);

        $handler = new MergeHandler($mock);
        $this->expectException(ChunkyException::class);

        $handler->merge('foo', 'test.txt');
    }

    /** @test */
    public function handler_merge_chunks_to_target()
    {
        Event::fake();
        $handler = new MergeHandler();
        $handler->chunksFilesystem()->folder('chunks');
        $handler->mergeFilesystem()->folder('result');

        $path = $handler->merge('foo', 'test.txt');

        Event::assertDispatched(ChunksMerged::class);
        Storage::assertMissing('chunks/foo');
        Storage::assertExists($path);
    }

    /** @test */
    public function handler_merge_chunks_with_temporary_files()
    {
        if (! $this->canTestS3()) {
            $this->markTestSkipped('Skipping S3 tests: missing .env values');
        }

        Storage::disk('s3_disk')->put('chunks/foo/0_foo.txt', 'Hello');
        Storage::disk('s3_disk')->put('chunks/foo/1_foo.txt', 'Hello');
        Storage::disk('s3_disk')->put('chunks/foo/2_foo.txt', 'Hello');

        $handler = new MergeHandler();

        $handler->chunksFilesystem()->disk('s3_disk');
        $handler->mergeFilesystem()->disk('s3_disk');

        $handler->merge(
            'foo',
            'merge.txt'
        );

        $this->assertTrue(Storage::disk('s3_disk')->exists('merge.txt'));
        Storage::disk('s3_disk')->delete(['chunks/foo/0_foo.txt', 'chunks/foo/1_foo.txt', 'chunks/foo/2_foo.txt', 'chunks/foo', 'merge.txt']);
    }
}
