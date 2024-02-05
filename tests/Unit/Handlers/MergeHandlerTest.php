<?php

namespace Jobtech\LaravelChunky\Tests\Unit\Handlers;

use Illuminate\Config\Repository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Jobtech\LaravelChunky\ChunkyManager;
use Jobtech\LaravelChunky\ChunkySettings;
use Jobtech\LaravelChunky\Tests\TestCase;
use Jobtech\LaravelChunky\Jobs\MergeChunks;
use Jobtech\LaravelChunky\Events\ChunksMerged;
use Jobtech\LaravelChunky\Handlers\MergeHandler;
use Jobtech\LaravelChunky\Exceptions\ChunkyException;
use Jobtech\LaravelChunky\Http\Requests\AddChunkRequest;
use Jobtech\LaravelChunky\Exceptions\ChunksIntegrityException;

/**
 * @internal
 */
class MergeHandlerTest extends TestCase
{
    /** @test */
    public function handlerIsCreatedWithManager()
    {
        $manager = new ChunkyManager(new ChunkySettings($this->app->make('config')));
        $handler = new MergeHandler($manager);

        $this->assertEquals($manager, $handler->manager());
    }

    /** @test */
    public function invalidChunksIntegrityThrowsException()
    {
        $request_mock = \Mockery::mock(AddChunkRequest::class);
        $request_mock->shouldReceive('fileInput')
            ->once()
            ->andReturn(UploadedFile::fake()->create('test.txt'));
        $request_mock->shouldReceive('chunkSizeInput')
            ->once()
            ->andReturn(10);
        $request_mock->shouldReceive('totalSizeInput')
            ->once()
            ->andReturn(100);

        $manager_mock = \Mockery::mock(ChunkyManager::class);
        $manager_mock->shouldReceive('settings')
            ->once()
            ->andReturn(new ChunkySettings(new Repository()));
        $manager_mock->shouldReceive('checkChunksIntegrity')
            ->once()
            ->with('foo', 10, 100)
            ->andReturn(false);

        $handler = new MergeHandler($manager_mock);

        $this->expectException(ChunksIntegrityException::class);

        $handler->dispatchMerge($request_mock, 'foo');
    }

    /** @test */
    public function handlerDispatchMerge()
    {
        Queue::fake();
        $request_mock = \Mockery::mock(AddChunkRequest::class);
        $request_mock->shouldReceive('fileInput')
            ->once()
            ->andReturn(UploadedFile::fake()->create('test.txt'));
        $request_mock->shouldReceive('chunkSizeInput')
            ->once()
            ->andReturn(10);
        $request_mock->shouldReceive('totalSizeInput')
            ->once()
            ->andReturn(100);

        $manager_mock = \Mockery::mock(ChunkyManager::class);
        $manager_mock->shouldReceive('settings')
            ->times(2)
            ->andReturn(new ChunkySettings(new Repository([
                'chunky' => [
                    'merge' => [
                        'connection' => 'default',
                        'queue' => 'foo',
                    ],
                ],
            ])));

        $handler = new MergeHandler($manager_mock);

        $handler->dispatchMerge($request_mock, 'foo');
        Queue::assertPushedOn('foo', MergeChunks::class);
    }

    /** @test */
    public function handlerThrowsExceptionWithInvalidChunkFolder()
    {
        $mock = \Mockery::mock(ChunkyManager::class);
        $mock->shouldReceive('isValidChunksFolder')
            ->once()
            ->with('foo')
            ->andReturn(false);

        $handler = new MergeHandler($mock);
        $this->expectException(ChunkyException::class);

        $handler->merge('foo', 'test.txt');
    }

    /** @test */
    public function handlerMergeChunksToTarget()
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
    public function handlerMergeChunksWithTemporaryFiles()
    {
        if (!$this->canTestS3()) {
            $this->markTestSkipped('Skipping S3 tests: missing .env values');
        }

        Storage::disk('s3_disk')->put('test/foo/0_foo.txt', 'Hello');
        Storage::disk('s3_disk')->put('test/foo/1_foo.txt', 'Hello');
        Storage::disk('s3_disk')->put('test/foo/2_foo.txt', 'Hello');

        $handler = new MergeHandler();

        $handler->chunksFilesystem()->disk('s3_disk');
        $handler->chunksFilesystem()->folder('test');
        $handler->mergeFilesystem()->disk('s3_disk');
        $handler->mergeFilesystem()->folder('test');

        $handler->merge(
            'foo',
            'merge.txt'
        );

        $this->assertTrue(Storage::disk('s3_disk')->exists('test/merge.txt'));

        Storage::disk('s3_disk')->delete(['test/foo/0_foo.txt', 'test/foo/1_foo.txt', 'test/foo/2_foo.txt', 'test/foo', 'test/merge.txt']);
    }
}
