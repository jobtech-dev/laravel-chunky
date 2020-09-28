<?php

namespace Jobtech\LaravelChunky\Tests\Unit;

use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Jobtech\LaravelChunky\Chunk;
use Jobtech\LaravelChunky\ChunksManager;
use Jobtech\LaravelChunky\ChunkySettings;
use Jobtech\LaravelChunky\Events\ChunkAdded;
use Jobtech\LaravelChunky\Exceptions\ChunksIntegrityException;
use Jobtech\LaravelChunky\Http\Requests\AddChunkRequest;
use Jobtech\LaravelChunky\Jobs\MergeChunks;
use Jobtech\LaravelChunky\Support\ChunksFilesystem;
use Jobtech\LaravelChunky\Tests\TestCase;
use Symfony\Component\HttpFoundation\File\File;

class ChunksManagerTest extends TestCase
{
    /**
     * @var \Illuminate\Config\Repository
     */
    private $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = $this->app->make('config');
    }

    /** @test */
    public function manager_retrieves_chunks_filesystem()
    {
        $manager = new ChunksManager(new ChunkySettings(
            $this->config
        ));

        $this->assertInstanceOf(ChunksFilesystem::class, $manager->chunksFilesystem());
        $this->assertInstanceOf(Factory::class, $manager->chunksFilesystem()->filesystem());
    }

    /** @test */
    public function manager_retrieves_chunks_disk()
    {
        $manager = new ChunksManager(new ChunkySettings(
            $this->config
        ));

        $this->assertEquals($this->config->get('chunky.disks.chunks.disk'), $manager->getChunksDisk());
    }

    /** @test */
    public function manager_retrieves_chunks_folder()
    {
        $manager = new ChunksManager(new ChunkySettings(
            $this->config
        ));

        $this->assertEquals($this->config->get('chunky.disks.chunks.folder').'/', $manager->getChunksFolder());
    }

    /** @test */
    public function manager_retrieves_chunks_options()
    {
        $manager = new ChunksManager(new ChunkySettings(
            $this->config
        ));

        $options = array_merge([
            'disk' => $this->config->get('chunky.disks.chunks.disk')
        ], $this->config->get('chunky.options.chunks'));

        $this->assertEquals($options, $manager->getChunksOptions());
    }

    public function manager_creates_temporary_files_from_chunks() {
        // TODO: integrate this test
        Storage::disk('s3')->put('chunks/foo/0_foo.txt', 'Hello ');
        Storage::disk('s3')->put('chunks/foo/1_foo.txt', 'World!');

        $chunks = collect([
            new Chunk(0, 'chunks/foo/0_foo.txt', '', false),
            new Chunk(1, 'chunks/foo/1_foo.txt', '', true)
        ]);

        $settings = $this->mock(ChunkySettings::class, function ($mock) {
            $mock->shouldReceive('chunksDisk')
                ->once()
                ->andReturn('s3');
            $mock->shouldReceive('additionalChunksOptions')
                ->once()
                ->andReturn([]);
        });
        $manager = new ChunksManager($settings);

        $manager->temporaryFiles('foo');

        // TODO: Assertions
    }

    /** @test */
    public function manager_retrieves_chunks_from_folder()
    {
        $manager = new ChunksManager(new ChunkySettings(
            $this->config
        ));

        $result = $manager->chunks('foo');
        $this->assertInstanceOf(Collection::class, $result);
        $result->each(function ($item) {
            $this->assertInstanceOf(Chunk::class, $item);

            $index = $item->getIndex();

            $this->assertEquals('chunks/foo/'.sprintf('%s_chunk.txt', $index), $item->getPath());
        });
    }

    /** @test */
    public function manager_checks_integrity_with_not_existing_folder()
    {
        $manager = new ChunksManager(new ChunkySettings(
            $this->config
        ));

        $this->assertTrue($manager->checkChunkIntegrity('foo', 3));
        $this->assertFalse($manager->checkChunkIntegrity('wrong_index', 3));
    }

    /** @test */
    public function manager_checks_integrity_with_not_existing_folder_and_different_default_index()
    {
        $this->app->config->set('chunky.index', 12);
        $manager = new ChunksManager(new ChunkySettings(
            $this->config
        ));

        $this->assertTrue($manager->checkChunkIntegrity('unexisting', 12));
        $this->assertTrue($manager->checkChunkIntegrity('foo', 15));
        $this->assertFalse($manager->checkChunkIntegrity('wrong_index', 15));
    }

    /** @test */
    public function manager_adds_chunk()
    {
        Event::fake();

        $file = new UploadedFile(__DIR__.'/../tmp/upload/fake_file.txt', 'fake_file.txt');

        $manager = new ChunksManager(new ChunkySettings(
            $this->config
        ));

        $chunk = $manager->addChunk($file, 3, 'foo');

        $this->assertInstanceOf(Chunk::class, $chunk);
        Storage::disk('local')->exists('foo/foo-file.mp4');

        Event::assertDispatched(ChunkAdded::class);
    }

    /** @test */
    public function manager_throws_exception_if_chunk_index_is_wrong()
    {
        $file = new UploadedFile(__DIR__.'/../tmp/upload/fake_file.txt', 'fake_file.txt');

        $manager = new ChunksManager(new ChunkySettings(
            $this->config
        ));

        $this->expectException(ChunksIntegrityException::class);

        $manager->addChunk($file, 2, 'foo');
    }

    /** @test */
    public function manager_adds_chunks()
    {
        Event::fake();

        $file = new UploadedFile(__DIR__.'/../tmp/upload/fake_file.txt', 'fake_file.txt');

        $manager = new ChunksManager(new ChunkySettings(
            $this->config
        ));

        $manager->addChunk($file, 0, 'bar');
        Event::assertDispatched(ChunkAdded::class);

        $manager->addChunk($file, 1, 'bar');
        Event::assertDispatched(ChunkAdded::class);

        Storage::assertExists('chunks/bar/0_fake-file.txt');
        Storage::assertExists('chunks/bar/1_fake-file.txt');
    }

    /** @test */
    public function manager_handle_chunk_request()
    {
        Queue::fake();
        Event::fake();

        $manager = new ChunksManager(new ChunkySettings(
            $this->config
        ));

        $mock = $this->mock(AddChunkRequest::class, function ($mock) {
            $upload = new UploadedFile(__DIR__.'/../tmp/upload/fake_file.txt', 'fake_file.txt');

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
        Event::assertDispatched(ChunkAdded::class);
        Storage::assertExists('chunks/foo-chunk/0_fake-file.txt');
    }

    /** @test */
    public function manager_handle_last_chunk_request()
    {
        Queue::fake();
        Event::fake();

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
                ->times(2)
                ->andReturn(5000);
            $mock->shouldReceive('chunkSizeInput')
                ->times(2)
                ->andReturn(5000);
        });

        $result = $manager->handle($mock, 'foo chunk');

        $this->assertInstanceOf(Chunk::class, $result);
        $this->assertTrue($result->isLast());

        Queue::assertPushed(MergeChunks::class);
        Event::assertDispatched(ChunkAdded::class);
        Storage::assertExists('chunks/foo-chunk/0_foo.mp4');
    }
}
