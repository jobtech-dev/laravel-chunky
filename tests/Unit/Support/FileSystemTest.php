<?php

namespace Jobtech\LaravelChunky\Tests\Unit\Support;

use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Support\Facades\Storage;
use Jobtech\LaravelChunky\Tests\TestCase;
use Jobtech\LaravelChunky\Tests\Unit\Support\Stubs\TestFilesystem;
use Mockery;

class FileSystemTest extends TestCase
{
    private TestFilesystem $filesystem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = $this->app->make(TestFilesystem::class);
    }

    /** @test */
    public function filesystem_set_disk()
    {
        $this->assertNull($this->filesystem->disk());
        $this->assertEquals('foo', $this->filesystem->disk('foo'));
    }

    /** @test */
    public function filesystem_set_folder()
    {
        $this->assertNull($this->filesystem->folder());
        $this->assertEquals('foo/', $this->filesystem->folder('foo'));
    }

    /** @test */
    public function filesystem_checks_if_path_exists() {
        $this->filesystem->folder('chunks');

        $this->assertTrue($this->filesystem->exists('foo'));
        $this->assertFalse($this->filesystem->exists('bar'));
    }

    /** @test */
    public function filesystem_lists_folders() {
        $this->filesystem->folder('chunks');

        $this->assertEquals([
            'chunks/foo',
            'chunks/wrong_index',
        ], $this->filesystem->folders());
    }

    /** @test */
    public function filesystem_lists_files_in_folder() {
        $this->filesystem->folder('chunks');

        $this->assertEquals([
            'chunks/foo/0_chunk.txt',
            'chunks/foo/1_chunk.txt',
            'chunks/foo/2_chunk.txt',
        ], $this->filesystem->list('foo'));
    }

    /** @test */
    public function filesystem_makes_directory() {
        $this->filesystem->folder('chunks');

        $this->filesystem->makeDirectory('bar');

        Storage::assertExists('chunks/bar');
    }

    /** @test */
    public function filesystem_returns_instance_of_itself() {
        $filesystem = TestFilesystem::instance([
            'disk' => 'foo',
            'folder' => 'bar'
        ]);

        $this->assertInstanceOf(TestFilesystem::class, $filesystem);
        $this->assertEquals('foo', $filesystem->disk());
        $this->assertEquals('bar/', $filesystem->folder());
    }

    public function filesystem_forward_calls_to_factory() {
        $mock = Mockery::mock(Factory::class);
        $mock->shouldReceive('foo')
            ->once()
            ->with('bar')
            ->andReturnTrue();

        $filesystem = new TestFilesystem($mock);

        $filesystem->foo('bar');
    }
}