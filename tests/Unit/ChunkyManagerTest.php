<?php

namespace Jobtech\LaravelChunky\Tests\Unit;

use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Jobtech\LaravelChunky\Chunk;
use Jobtech\LaravelChunky\ChunkyManager;
use Jobtech\LaravelChunky\ChunkySettings;
use Jobtech\LaravelChunky\Events\ChunkAdded;
use Jobtech\LaravelChunky\Events\ChunkDeleted;
use Jobtech\LaravelChunky\Exceptions\ChunksIntegrityException;
use Jobtech\LaravelChunky\Http\Requests\AddChunkRequest;
use Jobtech\LaravelChunky\Jobs\MergeChunks;
use Jobtech\LaravelChunky\Contracts\MergeManager;
use Jobtech\LaravelChunky\Strategies\FlysystemStrategy;
use Jobtech\LaravelChunky\Support\ChunksFilesystem;
use Jobtech\LaravelChunky\Support\MergeFilesystem;
use Jobtech\LaravelChunky\Tests\TestCase;
use Mockery;

class ChunkyManagerTest extends TestCase
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
    public function manager_is_created_with_filesystems()
    {
        $manager = new ChunkyManager(new ChunkySettings(
            $this->config
        ));

        $this->assertInstanceOf(ChunksFilesystem::class, $manager->chunksFilesystem());
        $this->assertInstanceOf(Factory::class, $manager->chunksFilesystem()->filesystem());

        $this->assertInstanceOf(MergeFilesystem::class, $manager->mergeFilesystem());
        $this->assertInstanceOf(Factory::class, $manager->mergeFilesystem()->filesystem());
    }

    /** @test */
    public function manager_retrieve_settings() {
        $settings = new ChunkySettings(
            $this->config
        );
        $manager = new ChunkyManager($settings);

        $this->assertEquals($settings, $manager->settings());
    }

    /** @test */
    public function manager_set_chunks_filesystem() {
        $manager = new ChunkyManager(new ChunkySettings(
            $this->config
        ));

        $manager->setChunksFilesystem('foo', 'bar');

        $this->assertEquals('foo', $manager->chunksFilesystem()->disk());
        $this->assertEquals('bar/', $manager->chunksFilesystem()->folder());
    }

    /** @test */
    public function manager_set_merge_filesystem() {
        $manager = new ChunkyManager(new ChunkySettings(
            $this->config
        ));

        $manager->setMergeFilesystem('foo', 'bar');

        $this->assertEquals('foo', $manager->mergeFilesystem()->disk());
        $this->assertEquals('bar/', $manager->mergeFilesystem()->folder());
    }

    /** @test */
    public function manager_retrieves_chunks_disk()
    {
        $manager = new ChunkyManager(new ChunkySettings(
            $this->config
        ));

        $this->assertEquals($this->config->get('chunky.disks.chunks.disk'), $manager->chunksDisk());
    }

    /** @test */
    public function manager_retrieves_merge_disk()
    {
        $manager = new ChunkyManager(new ChunkySettings(
            $this->config
        ));

        $this->assertEquals($this->config->get('chunky.disks.merge.disk'), $manager->mergeDisk());
    }

    /** @test */
    public function manager_retrieves_chunks_folder()
    {
        $manager = new ChunkyManager(new ChunkySettings(
            $this->config
        ));

        $this->assertEquals($this->config->get('chunky.disks.chunks.folder').'/', $manager->chunksFolder());
    }

    /** @test */
    public function manager_retrieves_merge_folder()
    {
        $manager = new ChunkyManager(new ChunkySettings(
            $this->config
        ));

        $this->assertEquals($this->config->get('chunky.disks.merge.folder'), $manager->mergeFolder());
    }

    /** @test */
    public function manager_retrieves_chunks_options()
    {
        $manager = new ChunkyManager(new ChunkySettings(
            $this->config
        ));

        $options = array_merge([
            'disk' => $this->config->get('chunky.disks.chunks.disk'),
        ], $this->config->get('chunky.options.chunks'));

        $this->assertEquals($options, $manager->chunksOptions());
    }

    /** @test */
    public function manager_retrieves_merge_options()
    {
        $manager = new ChunkyManager(new ChunkySettings(
            $this->config
        ));

        $options = $this->config->get('chunky.options.merge');

        $this->assertEquals($options, $manager->mergeOptions());
    }

    /** @test */
    public function manager_retrieves_chunks_from_folder()
    {
        $manager = new ChunkyManager(new ChunkySettings(
            $this->config
        ));

        $result = $manager->listChunks('foo');
        $this->assertInstanceOf(Collection::class, $result);
        $result->each(function ($item) {
            $this->assertInstanceOf(Chunk::class, $item);

            $index = $item->getIndex();

            $this->assertEquals('chunks/foo/'.sprintf('%s_chunk.txt', $index), $item->getPath());
        });
    }

    /** @test */
    public function manager_reads_chunk_from_object()
    {
        $manager = new ChunkyManager(new ChunkySettings(
            $this->config
        ));

        $result = $manager->readChunk(new Chunk(0, 'chunks/foo/0_chunk.txt', 'chunks'));
        $this->assertTrue(is_resource($result));
    }

    /** @test */
    public function manager_reads_chunk_from_path()
    {
        $manager = new ChunkyManager(new ChunkySettings(
            $this->config
        ));

        $result = $manager->readChunk('chunks/foo/0_chunk.txt');
        $this->assertTrue(is_resource($result));
    }

    /** @test */
    public function manager_adds_chunk()
    {
        Event::fake();

        $file = new UploadedFile(__DIR__.'/../tmp/upload/fake_file.txt', 'fake_file.txt');

        $manager = new ChunkyManager(new ChunkySettings(
            $this->config
        ));

        $chunk = $manager->addChunk($file, 0, 'test');

        $this->assertInstanceOf(Chunk::class, $chunk);
        Storage::disk('local')->exists('test/0_fake-file.mp4');

        Event::assertDispatched(ChunkAdded::class);
    }

    /** @test */
    public function manager_throws_exception_if_chunk_index_is_wrong()
    {
        $file = new UploadedFile(__DIR__.'/../tmp/upload/fake_file.txt', 'fake_file.txt');

        $manager = new ChunkyManager(new ChunkySettings(
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

        $manager = new ChunkyManager(new ChunkySettings(
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
    public function manager_deletes_chunks()
    {
        Event::fake();

        $manager = new ChunkyManager(new ChunkySettings(
            $this->config
        ));

        $fake_0 = $this->createFakeUpload();
        $fake_1 = $this->createFakeUpload();

        $manager->addChunk($fake_0, 0, 'test');
        $manager->addChunk($fake_1, 1, 'test');

        Storage::assertExists('chunks/test/0_fake-file.txt');
        Storage::assertExists('chunks/test/1_fake-file.txt');

        $manager->deleteChunks('chunks/test');

        Storage::assertMissing('chunks/test/0_fake-file.txt');
        Storage::assertMissing('chunks/test/1_fake-file.txt');
        Storage::assertMissing('chunks/test');

        Event::assertDispatched(ChunkDeleted::class);
    }

    /** @test */
    public function manager_delete_chunks_returns_true_on_unexisting_folder()
    {
        $manager = new ChunkyManager(new ChunkySettings(
            $this->config
        ));

        $this->assertTrue($manager->deleteChunks('unexisting'));
    }

    /** @test */
    public function manager_checks_if_chunks_folder_exists()
    {
        $manager = new ChunkyManager(new ChunkySettings(
            $this->config
        ));

        $this->assertFalse($manager->isValidChunksFolder('unexisting'));
        $this->assertFalse($manager->isValidChunksFolder('chunks/unexisting'));

        $manager->chunksFilesystem()->makeDirectory('test');

        $this->assertTrue($manager->isValidChunksFolder('test'));
        $this->assertTrue($manager->isValidChunksFolder('chunks/test'));
    }

    /** @test */
    public function manager_checks_integrity_with_not_existing_folder()
    {
        $manager = new ChunkyManager(new ChunkySettings(
            $this->config
        ));

        $this->assertTrue($manager->checkChunks('foo', 3));
        $this->assertFalse($manager->checkChunks('wrong_index', 3));
    }

    /** @test */
    public function manager_checks_integrity_with_not_existing_folder_and_different_default_index()
    {
        $this->config->set('chunky.index', 12);
        $manager = new ChunkyManager(new ChunkySettings(
            $this->config
        ));

        $this->assertTrue($manager->checkChunks('unexisting', 12));
        $this->assertTrue($manager->checkChunks('foo', 15));
        $this->assertFalse($manager->checkChunks('wrong_index', 15));
    }

    /** @test */
    public function manager_handle_chunk_request()
    {
        Queue::fake();
        Event::fake();

        $manager = new ChunkyManager(new ChunkySettings(
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

        $manager = $this->app->make(ChunkyManager::class);

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

    /** @test */
    public function manager_handle_merge_with_remote_filesystem()
    {
        if (! $this->canTestS3()) {
            $this->markTestSkipped('Skipping S3 tests: missing .env values');
        }

        Storage::disk('s3_disk')->put('chunks/foo/0_foo.txt', 'Hello');
        Storage::disk('s3_disk')->put('chunks/foo/1_foo.txt', 'Hello');
        Storage::disk('s3_disk')->put('chunks/foo/2_foo.txt', 'Hello');

        $manager = new ChunkyManager(new ChunkySettings(
            $this->config
        ));

        $manager->chunksFilesystem()->disk('s3_disk');
        $manager->mergeFilesystem()->disk('s3_disk');

        $chunk_size_0 = $manager->chunksFilesystem()->filesystem()->disk('s3_disk')->size('chunks/foo/0_foo.txt');
        $chunk_size_1 = $manager->chunksFilesystem()->filesystem()->disk('s3_disk')->size('chunks/foo/1_foo.txt');
        $chunk_size_2 = $manager->chunksFilesystem()->filesystem()->disk('s3_disk')->size('chunks/foo/2_foo.txt');
        $total_size = $chunk_size_0 + $chunk_size_1 + $chunk_size_2;

        $manager->merge(
            'foo',
            'merge.txt'
        );

        $this->assertTrue(Storage::disk('s3_disk')->exists('merge.txt'));
        $this->assertEquals($total_size, Storage::disk('s3_disk')->size('merge.txt'));

        Storage::disk('s3_disk')->delete(['chunks/foo/0_foo.txt', 'chunks/foo/1_foo.txt', 'chunks/foo/2_foo.txt', 'chunks/foo', 'merge.txt']);
    }

    /** @test */
    public function manager_merges_chunks_from_folder() {

        $manager = new ChunkyManager(new ChunkySettings(
            $this->config
        ));

        $manager->chunksFilesystem()->filesystem()->write('chunks/test/0_foo.txt', 'Hello');
        $manager->chunksFilesystem()->filesystem()->write('chunks/test/1_foo.txt', ' World');

        $manager->merge('chunks/test', 'bar/bar.txt');

        Storage::assertExists('bar/bar.txt');
        $this->assertEquals('Hello World', $manager->chunksFilesystem()->filesystem()->get('bar/bar.txt'));
        Storage::assertMissing('test/0_foo.txt');
        Storage::assertMissing('test/1_foo.txt');
        Storage::assertMissing('test');
    }
}
