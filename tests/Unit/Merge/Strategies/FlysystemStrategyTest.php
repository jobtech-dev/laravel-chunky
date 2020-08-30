<?php

namespace Jobtech\LaravelChunky\Tests\Unit\Merge\Strategies;

use Illuminate\Support\Facades\Storage;
use Jobtech\LaravelChunky\Strategies\FlysystemStrategy;
use Jobtech\LaravelChunky\Tests\TestCase;

class FlysystemStrategyTest extends TestCase
{
    /**
     * @var \Jobtech\LaravelChunky\ChunksManager
     */
    private $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = $this->app->make('chunky');
    }

    /** @test */
    public function strategy_merges_chunks()
    {
        $this->manager->chunksFilesystem()->makeDirectory('chunks/foo');

        $this->manager->chunksFilesystem()->write('foo/0_foo.txt', 'Hello');
        $this->manager->chunksFilesystem()->write('foo/1_foo.txt', ' World');

        $strategy = new FlysystemStrategy($this->manager);
        $strategy->chunksFolder('foo');
        $strategy->destination('bar/bar.txt');

        $strategy->merge();

        Storage::assertExists('bar/bar.txt');
        $this->assertEquals('Hello World', $strategy->mergeContents());
        Storage::assertMissing('foo/0_foo.txt');
        Storage::assertMissing('foo/1_foo.txt');
        Storage::assertMissing('foo');
    }
}
