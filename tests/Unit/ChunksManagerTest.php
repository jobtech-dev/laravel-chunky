<?php

namespace Jobtech\LaravelChunky\Tests\Unit;

use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Jobtech\LaravelChunky\Chunk;
use Jobtech\LaravelChunky\ChunksManager;
use Jobtech\LaravelChunky\ChunkySettings;
use Jobtech\LaravelChunky\Exceptions\ChunksIntegrityException;
use Jobtech\LaravelChunky\Http\Requests\AddChunkRequest;
use Jobtech\LaravelChunky\Jobs\MergeChunks;
use Jobtech\LaravelChunky\Tests\TestCase;
use Symfony\Component\HttpFoundation\File\File;

class ChunksManagerTest extends TestCase
{
    /** @test */
    public function manager_retrieves_chunks_filesystem()
    {
        $filesystem = $this->mock(Filesystem::class);
        $factory = $this->mock(Factory::class, function ($mock) use ($filesystem) {
            $mock->shouldReceive('disk')
                ->once()
                ->with('chunks')
                ->andReturn($filesystem);
        });
        $settings = $this->mock(ChunkySettings::class, function ($mock) {
            $mock->shouldReceive('chunksDisk')
                ->once()
                ->andReturn('chunks');
        });

        $manager = new ChunksManager($factory, $settings);

        $this->assertEquals($filesystem, $manager->chunksFilesystem());
    }

    /** @test */
    public function manager_retrieves_merge_filesystem()
    {
        $filesystem = $this->mock(Filesystem::class);
        $factory = $this->mock(Factory::class, function ($mock) use ($filesystem) {
            $mock->shouldReceive('disk')
                ->once()
                ->with('merge')
                ->andReturn($filesystem);
        });
        $settings = $this->mock(ChunkySettings::class, function ($mock) {
            $mock->shouldReceive('mergeDisk')
                ->once()
                ->andReturn('merge');
        });

        $manager = new ChunksManager($factory, $settings);

        $this->assertEquals($filesystem, $manager->mergeFilesystem());
    }

    /** @test */
    public function manager_retrieves_chunks_disk()
    {
        $filesystem = $this->mock(Factory::class);
        $settings = $this->mock(ChunkySettings::class, function ($mock) {
            $mock->shouldReceive('chunksDisk')
                ->once()
                ->andReturn('chunks');
        });

        $manager = new ChunksManager($filesystem, $settings);

        $this->assertEquals('chunks', $manager->getChunksDisk());
    }

    /** @test */
    public function manager_retrieves_merge_disk()
    {
        $filesystem = $this->mock(Factory::class);
        $settings = $this->mock(ChunkySettings::class, function ($mock) {
            $mock->shouldReceive('mergeDisk')
                ->once()
                ->andReturn('merge');
        });

        $manager = new ChunksManager($filesystem, $settings);

        $this->assertEquals('merge', $manager->getMergeDisk());
    }

    /** @test */
    public function manager_retrieves_chunks_folder()
    {
        $filesystem = $this->mock(Factory::class);
        $settings = $this->mock(ChunkySettings::class, function ($mock) {
            $mock->shouldReceive('chunksDisk')
                ->once()
                ->andReturn('chunks');
        });

        $manager = new ChunksManager($filesystem, $settings);

        $this->assertEquals('chunks', $manager->getChunksDisk());
    }

    /** @test */
    public function manager_retrieves_merge_folder()
    {
        $filesystem = $this->mock(Factory::class);
        $settings = $this->mock(ChunkySettings::class, function ($mock) {
            $mock->shouldReceive('mergeDisk')
                ->once()
                ->andReturn('merge');
        });

        $manager = new ChunksManager($filesystem, $settings);

        $this->assertEquals('merge', $manager->getMergeDisk());
    }

    /** @test */
    public function manager_retrieves_chunks_options()
    {
        $filesystem = $this->mock(Factory::class);
        $settings = $this->mock(ChunkySettings::class, function ($mock) {
            $mock->shouldReceive('chunksDisk')
                ->once()
                ->andReturn('chunks');
            $mock->shouldReceive('additionalChunksOptions')
                ->once()
                ->andReturn([
                    'foo' => 'bar',
                ]);
        });

        $manager = new ChunksManager($filesystem, $settings);

        $this->assertEquals([
            'disk' => 'chunks',
            'foo'  => 'bar',
        ], $manager->getChunksOptions());
    }

    /** @test */
    public function manager_retrieves_merge_options()
    {
        $filesystem = $this->mock(Factory::class);
        $settings = $this->mock(ChunkySettings::class, function ($mock) {
            $mock->shouldReceive('mergeDisk')
                ->once()
                ->andReturn('merge');
            $mock->shouldReceive('additionalMergeOptions')
                ->once()
                ->andReturn([
                    'foo' => 'bar',
                ]);
        });

        $manager = new ChunksManager($filesystem, $settings);

        $this->assertEquals([
            'disk' => 'merge',
            'foo'  => 'bar',
        ], $manager->getMergeOptions());
    }

    /** @test */
    public function manager_checks_integrity_with_not_existing_folder()
    {
        $filesystem = $this->mock(Filesystem::class, function ($mock) {
            $mock->shouldReceive('exists')
                ->times(2)
                ->with('chunks/foo')
                ->andReturn(false);
            $mock->shouldReceive('makeDirectory')
                ->once()
                ->with('chunks/foo')
                ->andReturn(true);

            $mock->shouldReceive('exists')
                ->times(2)
                ->with('chunks/foo')
                ->andReturn(true);

            $mock->shouldReceive('files')
                ->once()
                ->with('chunks/foo')
                ->andReturn(['foo']);
        });
        $factory = $this->mock(Factory::class, function ($mock) use ($filesystem) {
            $mock->shouldReceive('disk')
                ->with('chunks')
                ->andReturn($filesystem);
        });
        $settings = $this->mock(ChunkySettings::class, function ($mock) {
            $mock->shouldReceive('chunksFolder')
                ->times(2)
                ->andReturn('chunks/');
            $mock->shouldReceive('chunksDisk')
                ->times(6)
                ->andReturn('chunks');
            $mock->shouldReceive('defaultIndex')
                ->times(2)
                ->andReturn(0);
        });

        $manager = new ChunksManager($factory, $settings);

        $this->assertTrue($manager->checkChunkIntegrity('foo', 0));
        $this->assertFalse($manager->checkChunkIntegrity('foo', 0));
    }

    /** @test */
    public function manager_checks_integrity_with_not_existing_folder_and_different_default_index()
    {
        $filesystem = $this->mock(Filesystem::class, function ($mock) {
            $mock->shouldReceive('exists')
                ->times(2)
                ->with('chunks/foo')
                ->andReturn(false);
            $mock->shouldReceive('makeDirectory')
                ->once()
                ->with('chunks/foo')
                ->andReturn(true);

            $mock->shouldReceive('exists')
                ->times(4)
                ->with('chunks/foo')
                ->andReturn(true);
            $mock->shouldReceive('files')
                ->times(2)
                ->with('chunks/foo')
                ->andReturn(['foo']);
        });
        $factory = $this->mock(Factory::class, function ($mock) use ($filesystem) {
            $mock->shouldReceive('disk')
                ->with('chunks')
                ->andReturn($filesystem);
        });
        $settings = $this->mock(ChunkySettings::class, function ($mock) {
            $mock->shouldReceive('chunksFolder')
                ->times(3)
                ->andReturn('chunks/');
            $mock->shouldReceive('chunksDisk')
                ->times(9)
                ->andReturn('chunks');
            $mock->shouldReceive('defaultIndex')
                ->times(3)
                ->andReturn(12);
        });

        $manager = new ChunksManager($factory, $settings);

        $this->assertTrue($manager->checkChunkIntegrity('foo', 12));
        $this->assertTrue($manager->checkChunkIntegrity('foo', 13));
        $this->assertFalse($manager->checkChunkIntegrity('foo', 14));
    }

    /** @test */
    public function manager_throws_integrity_exception_if_folder_cannot_be_created()
    {
        $filesystem = $this->mock(Filesystem::class, function ($mock) {
            $mock->shouldReceive('exists')
                ->times(2)
                ->with('chunks/foo')
                ->andReturn(false);
            $mock->shouldReceive('makeDirectory')
                ->once()
                ->with('chunks/foo')
                ->andReturn(false);
        });
        $factory = $this->mock(Factory::class, function ($mock) use ($filesystem) {
            $mock->shouldReceive('disk')
                ->with('chunks')
                ->andReturn($filesystem);
        });
        $settings = $this->mock(ChunkySettings::class, function ($mock) {
            $mock->shouldReceive('chunksFolder')
                ->once()
                ->andReturn('chunks/');
            $mock->shouldReceive('chunksDisk')
                ->times(3)
                ->andReturn('chunks');
            $mock->shouldReceive('defaultIndex')
                ->once()
                ->andReturn(0);
        });

        $manager = new ChunksManager($factory, $settings);

        $this->expectException(ChunksIntegrityException::class);

        $manager->checkChunkIntegrity('foo', 0);
    }

    /** @test */
    public function manager_adds_chunk()
    {
        $file = UploadedFile::fake()->create('foo file.mp4', 2048);
        $filesystem = $this->mock(Filesystem::class, function ($mock) use ($file) {
            $result = new File('/chunks/foo-file/0_foo-file.mp4', false);

            $mock->shouldReceive('exists')
                ->times(2)
                ->with('chunks/foo')
                ->andReturn(false);
            $mock->shouldReceive('makeDirectory')
                ->once()
                ->with('chunks/foo')
                ->andReturn(true);
            $mock->shouldReceive('putFileAs')
                ->once()
                ->with('chunks/foo', $file, '0_foo-file.mp4', [])
                ->andReturn($result);
        });
        $factory = $this->mock(Factory::class, function ($mock) use ($filesystem) {
            $mock->shouldReceive('disk')
                ->with('chunks')
                ->andReturn($filesystem);
        });
        $settings = $this->mock(ChunkySettings::class, function ($mock) {
            $mock->shouldReceive('chunksFolder')
                ->times(2)
                ->andReturn('chunks/');
            $mock->shouldReceive('chunksDisk')
                ->times(4)
                ->andReturn('chunks');
            $mock->shouldReceive('defaultIndex')
                ->once()
                ->andReturn(0);
            $mock->shouldReceive('additionalChunksOptions')
                ->once()
                ->andReturn([]);
        });

        $manager = new ChunksManager($factory, $settings);

        $chunk = $manager->addChunk($file, 0, 'foo');

        $this->assertInstanceOf(Chunk::class, $chunk);
    }

    /** @test */
    public function manager_throws_exception_if_chunk_index_is_wrong()
    {
        $file = UploadedFile::fake()->create('foo file.mp4', 2048);
        $filesystem = $this->mock(Filesystem::class, function ($mock) {
            $mock->shouldReceive('exists')
                ->times(2)
                ->with('chunks/foo')
                ->andReturn(true);
            $mock->shouldReceive('files')
                ->once()
                ->with('chunks/foo')
                ->andReturn(['0_foo-file.mp4']);
        });
        $factory = $this->mock(Factory::class, function ($mock) use ($filesystem) {
            $mock->shouldReceive('disk')
                ->with('chunks')
                ->andReturn($filesystem);
        });
        $settings = $this->mock(ChunkySettings::class, function ($mock) {
            $mock->shouldReceive('chunksFolder')
                ->once()
                ->andReturn('chunks/');
            $mock->shouldReceive('chunksDisk')
                ->times(3)
                ->andReturn('chunks');
            $mock->shouldReceive('defaultIndex')
                ->once()
                ->andReturn(0);
        });

        $manager = new ChunksManager($factory, $settings);

        $this->expectException(ChunksIntegrityException::class);

        $manager->addChunk($file, 2, 'foo');
    }

    /** @test */
    public function manager_adds_chunks()
    {
        $file = UploadedFile::fake()->create('foo file.mp4', 2048);

        $manager = $this->app->make(ChunksManager::class);

        $manager->addChunk($file, 0, 'foo');
        $manager->addChunk($file, 1, 'foo');

        Storage::assertExists('chunks/foo/0_foo-file.mp4');
        Storage::assertExists('chunks/foo/1_foo-file.mp4');
    }

    /** @test */
    public function manager_handle_chunk_request()
    {
        Queue::fake();

        $manager = $this->app->make(ChunksManager::class);

        $mock = $this->mock(AddChunkRequest::class, function ($mock) {
            $upload = UploadedFile::fake()->create('foo.mp4', 5000);

            $mock->shouldReceive('fileInput')
                ->times(2)
                ->andReturn($upload);
            $mock->shouldReceive('indexInput')
                ->times(2)
                ->andReturn(0);
            $mock->shouldReceive('totalSizeInput')
                ->once()
                ->andReturn(10000);
            $mock->shouldReceive('chunkSizeInput')
                ->once()
                ->andReturn(5000);
        });

        $result = $manager->handle($mock, 'foo chunk');

        $this->assertInstanceOf(Chunk::class, $result);

        Queue::assertNothingPushed();
        Storage::assertExists('chunks/foo-chunk/0_foo.mp4');
    }

    /** @test */
    public function manager_handle_last_chunk_request()
    {
        Queue::fake();

        $manager = $this->app->make(ChunksManager::class);

        $mock = $this->mock(AddChunkRequest::class, function ($mock) {
            $upload = UploadedFile::fake()->create('foo.mp4', 5000);

            $mock->shouldReceive('fileInput')
                ->times(3)
                ->andReturn($upload);
            $mock->shouldReceive('indexInput')
                ->times(2)
                ->andReturn(0);
            $mock->shouldReceive('totalSizeInput')
                ->once()
                ->andReturn(5000);
            $mock->shouldReceive('chunkSizeInput')
                ->once()
                ->andReturn(5000);
        });

        $result = $manager->handle($mock, 'foo chunk');

        $this->assertInstanceOf(Chunk::class, $result);

        Queue::assertPushed(MergeChunks::class);
        Storage::assertExists('chunks/foo-chunk/0_foo.mp4');
    }
}
