<?php

namespace Jobtech\LaravelChunky\Tests\Unit\Strategies;

use Illuminate\Support\Facades\Storage;
use Jobtech\LaravelChunky\Contracts\ChunksManager;
use Jobtech\LaravelChunky\Contracts\MergeManager;
use Jobtech\LaravelChunky\Strategies\FlysystemStrategy;
use Jobtech\LaravelChunky\Tests\TestCase;

class FlysystemStrategyTest extends TestCase
{
    /**
     * @var \Jobtech\LaravelChunky\ChunksManager
     */
    private $chunks_manager;

    /**
     * @var \Jobtech\LaravelChunky\MergeManager
     */
    private $merge_manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->chunks_manager = $this->app->make(ChunksManager::class);
        $this->merge_manager = $this->app->make(MergeManager::class);
    }

    /** @test */
    public function strategy_merges_chunks()
    {
        $this->chunks_manager->chunksFilesystem()->filesystem()->write('chunks/test/0_foo.txt', 'Hello');
        $this->chunks_manager->chunksFilesystem()->filesystem()->write('chunks/test/1_foo.txt', ' World');

        $strategy = new FlysystemStrategy();
        $strategy->chunksManager($this->chunks_manager);
        $strategy->mergeManager($this->merge_manager);
        $strategy->chunksFolder('chunks/test');
        $strategy->destination('bar/bar.txt');

        $strategy->merge();

        Storage::assertExists('bar/bar.txt');
        $this->assertEquals('Hello World', $this->chunks_manager->chunksFilesystem()->filesystem()->get('bar/bar.txt'));
        Storage::assertMissing('test/0_foo.txt');
        Storage::assertMissing('test/1_foo.txt');
        Storage::assertMissing('test');
    }
}
