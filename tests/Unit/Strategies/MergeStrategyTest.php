<?php

namespace Jobtech\LaravelChunky\Tests\Unit\Strategies;

use Jobtech\LaravelChunky\Contracts\ChunksManager;
use Jobtech\LaravelChunky\Contracts\MergeManager;
use Jobtech\LaravelChunky\Exceptions\StrategyException;
use Jobtech\LaravelChunky\Tests\TestCase;
use Jobtech\LaravelChunky\Tests\Unit\Strategies\Stubs\TestMergeStrategy;

class MergeStrategyTest extends TestCase
{
    /**
     * @var \Jobtech\LaravelChunky\ChunksManager
     */
    private $chunks_manager;
    /**
     * @var \Jobtech\LaravelChunky\MergeManager
     */
    private $merge_manager;

    public function setUp(): void
    {
        parent::setUp();

        $this->chunks_manager = $this->app->make(ChunksManager::class);
        $this->merge_manager = $this->app->make(MergeManager::class);
    }

    /** @test */
    public function strategy_sets_and_retrieve_manager()
    {
        $mock = $this->mock(ChunksManager::class);
        $strategy = new TestMergeStrategy();

        $this->assertEquals($this->chunks_manager, $strategy->chunksManager($this->chunks_manager));
        $this->assertEquals($mock, $strategy->chunksManager($mock));
        $this->assertNotEquals($this->chunks_manager, $strategy->chunksManager());
    }

    /** @test */
    public function strategy_throws_strategy_exception_on_null_chunks_folder()
    {
        $strategy = new TestMergeStrategy();

        $this->expectException(StrategyException::class);

        $strategy->chunksFolder();
    }

    /** @test */
    public function strategy_throws_strategy_exception_on_unexisting_chunks_folder()
    {
        $strategy = new TestMergeStrategy();
        $strategy->chunksManager($this->chunks_manager);

        $this->expectException(StrategyException::class);

        $strategy->chunksFolder('unexisting');
    }

    /** @test */
    public function strategy_sets_and_retrieve_chunks_folder()
    {
        $strategy = new TestMergeStrategy();
        $strategy->chunksManager($this->chunks_manager);

        $this->assertEquals('foo', $strategy->chunksFolder('foo'));
    }

    /** @test */
    public function strategy_sets_and_retrieve_destination()
    {
        $strategy = new TestMergeStrategy();
        $strategy->mergeManager($this->merge_manager);

        $this->assertEquals('foo', $strategy->destination('foo'));
    }
}
