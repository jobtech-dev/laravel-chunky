<?php

namespace Jobtech\LaravelChunky\Tests\Unit\Support;

use Jobtech\LaravelChunky\Chunk;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Jobtech\LaravelChunky\Tests\TestCase;
use Jobtech\LaravelChunky\Events\ChunkDeleted;
use Jobtech\LaravelChunky\Support\ChunksFilesystem;
use Jobtech\LaravelChunky\Exceptions\ChunkyException;

/**
 * @internal
 */
class ChunksFilesystemTest extends TestCase
{
    private ChunksFilesystem $filesystem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = ChunksFilesystem::instance([
            'folder' => 'chunks',
        ]);
    }

    /** @test */
    public function filesystemListsChunksInFolder()
    {
        $result = $this->filesystem->listChunks('foo');

        $result->each(function ($item, $index) {
            $this->assertInstanceOf(Chunk::class, $item);

            $this->assertEquals($index, $item->getIndex());
            $this->assertEquals("chunks/foo/{$index}_chunk.txt", $item->getPath());
        });
    }

    /** @test */
    public function filesystemListsChunksInFolderWithMoreThan10Chunks()
    {
        for ($i = 0; $i < 11; $i++) {
            $file = UploadedFile::fake()->create('test_chunk.txt');
            $chunk = Chunk::create($file, $i);

            $this->filesystem->store($chunk, 'manyChunks');
        }

        $result = $this->filesystem->listChunks('manyChunks');

        $result->each(function (Chunk $item, int $index) {
            if ($index == 10) {
                $this->assertTrue($item->isLast());
            } else {
                $this->assertFalse($item->isLast());
            }
        });
    }

    /** @test */
    public function filesystemListsFolders()
    {
        $result = $this->filesystem->chunkFolders();

        $this->assertEquals([
            'chunks/foo',
            'chunks/wrong_index',
        ], $result);
    }

    /** @test */
    public function filesystemCountsChunksInFolder()
    {
        $this->assertEquals(3, $this->filesystem->chunksCount('foo'));
    }

    /** @test */
    public function filesystemReturnsChunkSize()
    {
        $this->assertEquals(5, $this->filesystem->chunkSize('foo/0_chunk.txt'));
    }

    /** @test */
    public function filesystemReadsChunk()
    {
        $this->assertIsResource($this->filesystem->readChunk('foo/0_chunk.txt'));
    }

    /** @test */
    public function filesystemThrowsExceptionWithInvalidChunk()
    {
        $chunk = new Chunk(0, '');

        $this->expectException(ChunkyException::class);

        $this->filesystem->store($chunk, '');
    }

    /** @test */
    public function filesystemStoresChunks()
    {
        $chunk = new Chunk(0, UploadedFile::fake()->create('test.txt'));

        $this->filesystem->store($chunk, '');

        Storage::assertExists('chunks/0_test.txt');
    }

    /** @test */
    public function unexistingChunkReturnsTrueOnDelete()
    {
        $this->assertTrue($this->filesystem->deleteChunk(new Chunk(999, 'chunks/foo/999_chunk.txt')));
    }

    /** @test */
    public function filesystemDeletesChunk()
    {
        Event::fake();
        $this->filesystem->deleteChunk(new Chunk(0, 'chunks/foo/0_chunk.txt'));

        Storage::assertMissing('chunks/foo/0_chunk.txt');
        Event::assertDispatched(ChunkDeleted::class);
    }

    /** @test */
    public function unexistingChunksFolderReturnsTrueOnDelete()
    {
        $this->assertTrue($this->filesystem->delete('foo/bar/baz'));
    }

    /** @test */
    public function filesystemDeletesChunksFolder()
    {
        Event::fake();
        $this->filesystem->delete('foo');

        Storage::assertMissing('chunks/foo/0_chunk.txt');
        Storage::assertMissing('chunks/foo/1_chunk.txt');
        Storage::assertMissing('chunks/foo/2_chunk.txt');
        Storage::assertMissing('chunks/foo');
        Event::assertDispatched(ChunkDeleted::class);
    }

    /** @test */
    public function filesystemConcatenateChunks()
    {
        $this->filesystem->concatenate('chunks/foo/concatenated.txt', [
            'chunks/foo/0_chunk.txt',
            'chunks/foo/1_chunk.txt',
            'chunks/foo/2_chunk.txt',
        ]);

        Storage::assertExists('chunks/foo/concatenated.txt');
    }
}
