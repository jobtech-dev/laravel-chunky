<?php

namespace Jobtech\LaravelChunky\Tests\Unit;

use Jobtech\LaravelChunky\Chunk;
use Illuminate\Config\Repository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Jobtech\LaravelChunky\ChunkyManager;
use Jobtech\LaravelChunky\ChunkySettings;
use Jobtech\LaravelChunky\Tests\TestCase;
use Jobtech\LaravelChunky\Jobs\MergeChunks;
use Illuminate\Contracts\Filesystem\Factory;
use Jobtech\LaravelChunky\Events\ChunkAdded;
use Jobtech\LaravelChunky\Events\ChunkDeleted;
use Jobtech\LaravelChunky\Support\MergeFilesystem;
use Jobtech\LaravelChunky\Support\ChunksFilesystem;
use Jobtech\LaravelChunky\Http\Requests\AddChunkRequest;
use Jobtech\LaravelChunky\Exceptions\ChunksIntegrityException;

/**
 * @internal
 */
class ChunkyManagerTest extends TestCase
{
    /**
     * @var Repository
     */
    private $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = $this->app->make('config');
    }

    /** @test */
    public function managerIsCreatedWithFilesystems()
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
    public function managerRetrieveSettings()
    {
        $settings = new ChunkySettings(
            $this->config
        );
        $manager = new ChunkyManager($settings);

        $this->assertEquals($settings, $manager->settings());
    }

    /** @test */
    public function managerSetChunksFilesystem()
    {
        $manager = new ChunkyManager(new ChunkySettings(
            $this->config
        ));

        $manager->setChunksFilesystem('foo', 'bar');

        $this->assertEquals('foo', $manager->chunksFilesystem()->disk());
        $this->assertEquals('bar/', $manager->chunksFilesystem()->folder());
    }

    /** @test */
    public function managerSetMergeFilesystem()
    {
        $manager = new ChunkyManager(new ChunkySettings(
            $this->config
        ));

        $manager->setMergeFilesystem('foo', 'bar');

        $this->assertEquals('foo', $manager->mergeFilesystem()->disk());
        $this->assertEquals('bar/', $manager->mergeFilesystem()->folder());
    }

    /** @test */
    public function managerRetrievesChunksDisk()
    {
        $manager = new ChunkyManager(new ChunkySettings(
            $this->config
        ));

        $this->assertEquals($this->config->get('chunky.disks.chunks.disk'), $manager->chunksDisk());
    }

    /** @test */
    public function managerRetrievesMergeDisk()
    {
        $manager = new ChunkyManager(new ChunkySettings(
            $this->config
        ));

        $this->assertEquals($this->config->get('chunky.disks.merge.disk'), $manager->mergeDisk());
    }

    /** @test */
    public function managerRetrievesChunksFolder()
    {
        $manager = new ChunkyManager(new ChunkySettings(
            $this->config
        ));

        $this->assertEquals($this->config->get('chunky.disks.chunks.folder').'/', $manager->chunksFolder());
    }

    /** @test */
    public function managerRetrievesMergeFolder()
    {
        $manager = new ChunkyManager(new ChunkySettings(
            $this->config
        ));

        $this->assertEquals($this->config->get('chunky.disks.merge.folder'), $manager->mergeFolder());
    }

    /** @test */
    public function managerRetrievesChunksOptions()
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
    public function managerRetrievesMergeOptions()
    {
        $manager = new ChunkyManager(new ChunkySettings(
            $this->config
        ));

        $options = $this->config->get('chunky.options.merge');

        $this->assertEquals($options, $manager->mergeOptions());
    }

    /** @test */
    public function managerRetrievesChunksFromFolder()
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
    public function managerReadsChunkFromObject()
    {
        $manager = new ChunkyManager(new ChunkySettings(
            $this->config
        ));

        $result = $manager->readChunk(new Chunk(0, 'chunks/foo/0_chunk.txt', 'chunks'));
        $this->assertTrue(is_resource($result));
    }

    /** @test */
    public function managerReadsChunkFromPath()
    {
        $manager = new ChunkyManager(new ChunkySettings(
            $this->config
        ));

        $result = $manager->readChunk('chunks/foo/0_chunk.txt');
        $this->assertTrue(is_resource($result));
    }

    /** @test */
    public function managerAddsChunk()
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
    public function managerThrowsExceptionIfChunkIndexIsWrong()
    {
        $file = new UploadedFile(__DIR__.'/../tmp/upload/fake_file.txt', 'fake_file.txt');

        $manager = new ChunkyManager(new ChunkySettings(
            $this->config
        ));

        $this->expectException(ChunksIntegrityException::class);

        $manager->addChunk($file, 2, 'foo');
    }

    /** @test */
    public function managerAddsChunks()
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
    public function managerDeletesChunks()
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
    public function managerDeleteChunksReturnsTrueOnUnexistingFolder()
    {
        $manager = new ChunkyManager(new ChunkySettings(
            $this->config
        ));

        $this->assertTrue($manager->deleteChunks('unexisting'));
    }

    /** @test */
    public function managerChecksIfChunksFolderExists()
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
    public function managerChecksIntegrityWithNotExistingFolder()
    {
        $manager = new ChunkyManager(new ChunkySettings(
            $this->config
        ));

        $this->assertTrue($manager->checkChunks('foo', 3));
        $this->assertFalse($manager->checkChunks('wrong_index', 3));
    }

    /** @test */
    public function managerChecksIntegrityWithNotExistingFolderAndDifferentDefaultIndex()
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
    public function managerHandleChunkRequest()
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
    public function managerHandleLastChunkRequest()
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
    public function managerHandleMergeWithRemoteFilesystem()
    {
        if (!$this->canTestS3()) {
            $this->markTestSkipped('Skipping S3 tests: missing .env values');
        }

        Storage::disk('s3_disk')->put('test/foo/0_foo.txt', 'Hello');
        Storage::disk('s3_disk')->put('test/foo/1_foo.txt', 'Hello');
        Storage::disk('s3_disk')->put('test/foo/2_foo.txt', 'Hello');

        $manager = new ChunkyManager(new ChunkySettings(
            $this->config
        ));

        $manager->chunksFilesystem()->disk('s3_disk');
        $manager->chunksFilesystem()->folder('test');
        $manager->mergeFilesystem()->disk('s3_disk');
        $manager->mergeFilesystem()->folder('test');

        $chunk_size_0 = Storage::disk('s3_disk')->size('test/foo/0_foo.txt');
        $chunk_size_1 = Storage::disk('s3_disk')->size('test/foo/1_foo.txt');
        $chunk_size_2 = Storage::disk('s3_disk')->size('test/foo/2_foo.txt');
        $total_size = $chunk_size_0 + $chunk_size_1 + $chunk_size_2;

        $manager->merge(
            'foo',
            'merge.txt'
        );

        $this->assertTrue(Storage::disk('s3_disk')->exists('test/merge.txt'));
        $this->assertEquals($total_size, Storage::disk('s3_disk')->size('test/merge.txt'));

        Storage::disk('s3_disk')->delete(['test/foo/0_foo.txt', 'test/foo/1_foo.txt', 'test/foo/2_foo.txt', 'test/foo', 'test/merge.txt']);
    }

    /** @test */
    public function managerMergesChunksFromFolder()
    {
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
