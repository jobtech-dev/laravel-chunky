<?php

namespace Jobtech\LaravelChunky\Tests\Unit\Support;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Jobtech\LaravelChunky\Tests\TestCase;
use Jobtech\LaravelChunky\Events\MergeAdded;
use Jobtech\LaravelChunky\Support\MergeFilesystem;

/**
 * @internal
 */
class MergeFilesystemTest extends TestCase
{
    private MergeFilesystem $filesystem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = MergeFilesystem::instance([
            'folder' => 'foo',
        ]);
    }

    /** @test */
    public function filesystemStoresFile()
    {
        Event::fake();
        $this->filesystem->store('text.txt', $this->filesystem->readStream('chunks/foo/0_chunk.txt'));

        Storage::assertExists('foo/text.txt');
        Event::assertDispatched(MergeAdded::class);
    }
}
