<?php

namespace Jobtech\LaravelChunky\Tests\Unit\Support;

use Illuminate\Support\Facades\Storage;
use Jobtech\LaravelChunky\Tests\TestCase;
use Illuminate\Contracts\Filesystem\Factory;
use Jobtech\LaravelChunky\Tests\Unit\Support\Stubs\TestFilesystem;

/**
 * @internal
 */
class FileSystemTest extends TestCase
{
    private TestFilesystem $filesystem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = $this->app->make(TestFilesystem::class);
    }

    /** @test */
    public function filesystemSetDisk()
    {
        $this->assertNull($this->filesystem->disk());
        $this->assertEquals('foo', $this->filesystem->disk('foo'));
    }

    /** @test */
    public function filesystemSetFolder()
    {
        $this->assertNull($this->filesystem->folder());
        $this->assertEquals('foo/', $this->filesystem->folder('foo'));
    }

    /** @test */
    public function filesystemChecksIfPathExists()
    {
        $this->filesystem->folder('chunks');

        $this->assertTrue($this->filesystem->exists('foo'));
        $this->assertFalse($this->filesystem->exists('bar'));
    }

    /** @test */
    public function filesystemListsFolders()
    {
        $this->filesystem->folder('chunks');

        $this->assertEquals([
            'chunks/foo',
            'chunks/wrong_index',
        ], $this->filesystem->folders());
    }

    /** @test */
    public function filesystemListsFilesInFolder()
    {
        $this->filesystem->folder('chunks');

        $this->assertEquals([
            'chunks/foo/0_chunk.txt',
            'chunks/foo/1_chunk.txt',
            'chunks/foo/2_chunk.txt',
        ], $this->filesystem->list('foo'));
    }

    /** @test */
    public function filesystemMakesDirectory()
    {
        $this->filesystem->folder('chunks');

        $this->filesystem->makeDirectory('bar');

        Storage::assertExists('chunks/bar');
    }

    /** @test */
    public function filesystemReturnsInstanceOfItself()
    {
        $filesystem = TestFilesystem::instance([
            'disk' => 'foo',
            'folder' => 'bar',
        ]);

        $this->assertInstanceOf(TestFilesystem::class, $filesystem);
        $this->assertEquals('foo', $filesystem->disk());
        $this->assertEquals('bar/', $filesystem->folder());
    }

    public function filesystem_forward_calls_to_factory()
    {
        $mock = \Mockery::mock(Factory::class);
        $mock->shouldReceive('foo')
            ->once()
            ->with('bar')
            ->andReturnTrue();

        $filesystem = new TestFilesystem($mock);

        $filesystem->foo('bar');
    }
}
