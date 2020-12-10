<?php

namespace Jobtech\LaravelChunky\Tests\Unit\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Jobtech\LaravelChunky\Chunk;
use Jobtech\LaravelChunky\Events\ChunkDeleted;
use Jobtech\LaravelChunky\Exceptions\ChunkyException;
use Jobtech\LaravelChunky\Support\ChunksFilesystem;
use Jobtech\LaravelChunky\Tests\TestCase;

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
    public function filesystem_lists_chunks_in_folder()
    {
        $result = $this->filesystem->listChunks('foo');

        $result->each(function ($item, $index) {
            $this->assertInstanceOf(Chunk::class, $item);

            $this->assertEquals($index, $item->getIndex());
            $this->assertEquals("chunks/foo/{$index}_chunk.txt", $item->getPath());
        });
    }

    /** @test */
    public function filesystem_lists_chunks_in_folder_with_more_than_10_chunks()
    {
        for($i = 0; $i < 11; $i++) {
            $file = UploadedFile::fake()->create('test_chunk.txt');
            $chunk = Chunk::create($file, $i);

            $this->filesystem->store($chunk, 'manyChunks');
        }

        $result = $this->filesystem->listChunks('manyChunks');

        $result->each(function (Chunk $item, int $index) {
            if($index == 10) {
                $this->assertTrue($item->isLast());
            } else {
                $this->assertFalse($item->isLast());
            }
        });
    }

    /** @test */
    public function filesystem_lists_folders()
    {
        $result = $this->filesystem->chunkFolders();

        $this->assertEquals([
            'chunks/foo',
            'chunks/wrong_index',
        ], $result);
    }

    /** @test */
    public function filesystem_counts_chunks_in_folder()
    {
        $this->assertEquals(3, $this->filesystem->chunksCount('foo'));
    }

    /** @test */
    public function filesystem_returns_chunk_size()
    {
        $this->assertEquals(5, $this->filesystem->chunkSize('foo/0_chunk.txt'));
    }

    /** @test */
    public function filesystem_reads_chunk()
    {
        $this->assertIsResource($this->filesystem->readChunk('foo/0_chunk.txt'));
    }

    /** @test */
    public function filesystem_throws_exception_with_invalid_chunk()
    {
        $chunk = new Chunk(0, '');

        $this->expectException(ChunkyException::class);

        $this->filesystem->store($chunk, '');
    }

    /** @test */
    public function filesystem_stores_chunks()
    {
        $chunk = new Chunk(0, UploadedFile::fake()->create('test.txt'));

        $this->filesystem->store($chunk, '');

        Storage::assertExists('chunks/0_test.txt');
    }

    /** @test */
    public function unexisting_chunk_returns_true_on_delete()
    {
        $this->assertTrue($this->filesystem->deleteChunk(new Chunk(999, 'chunks/foo/999_chunk.txt')));
    }

    /** @test */
    public function filesystem_deletes_chunk()
    {
        Event::fake();
        $this->filesystem->deleteChunk(new Chunk(0, 'chunks/foo/0_chunk.txt'));

        Storage::assertMissing('chunks/foo/0_chunk.txt');
        Event::assertDispatched(ChunkDeleted::class);
    }

    /** @test */
    public function unexisting_chunks_folder_returns_true_on_delete()
    {
        $this->assertTrue($this->filesystem->delete('foo/bar/baz'));
    }

    /** @test */
    public function filesystem_deletes_chunks_folder()
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
    public function filesystem_concatenate_chunks()
    {
        $this->filesystem->concatenate('chunks/foo/concatenated.txt', [
            'chunks/foo/0_chunk.txt',
            'chunks/foo/1_chunk.txt',
            'chunks/foo/2_chunk.txt',
        ]);

        Storage::assertExists('chunks/foo/concatenated.txt');
    }
}
