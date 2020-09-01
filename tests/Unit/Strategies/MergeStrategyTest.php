<?php

namespace Jobtech\LaravelChunky\Tests\Unit\Strategies;

use Illuminate\Http\UploadedFile;
use Jobtech\LaravelChunky\Contracts\ChunksManager;
use Jobtech\LaravelChunky\Exceptions\StrategyException;
use Jobtech\LaravelChunky\Tests\TestCase;
use Jobtech\LaravelChunky\Tests\Unit\Strategies\Stubs\TestMergeStrategy;

class MergeStrategyTest extends TestCase
{
    /**
     * @var \Jobtech\LaravelChunky\ChunksManager
     */
    private $manager;

    public function setUp(): void
    {
        parent::setUp();

        $this->manager = $this->app->make('chunky');
    }

    /** @test */
    public function strategy_sets_and_retrieve_manager()
    {
        $mock = $this->mock(ChunksManager::class);
        $strategy = new TestMergeStrategy($this->manager);

        $this->assertEquals($this->manager, $strategy->manager());
        $this->assertEquals($mock, $strategy->manager($mock));
        $this->assertNotEquals($this->manager, $strategy->manager());
    }

    /** @test */
    public function strategy_throws_strategy_exception_on_null_chunks_folder()
    {
        $strategy = new TestMergeStrategy($this->manager);

        $this->expectException(StrategyException::class);

        $strategy->chunksFolder();
    }

    /** @test */
    public function strategy_throws_strategy_exception_on_unexisting_chunks_folder()
    {
        $strategy = new TestMergeStrategy($this->manager);

        $this->expectException(StrategyException::class);

        $strategy->chunksFolder('foo');
    }

    /** @test */
    public function strategy_sets_and_retrieve_chunks_folder()
    {
        $this->manager->chunksFilesystem()->makeDirectory('chunks/foo');

        $strategy = new TestMergeStrategy($this->manager);

        $this->assertEquals('foo', $strategy->chunksFolder('foo'));
    }

    /** @test */
    public function strategy_throws_strategy_exception_on_null_destination()
    {
        $strategy = new TestMergeStrategy($this->manager);

        $this->expectException(StrategyException::class);

        $strategy->destination();
    }

    /** @test */
    public function strategy_sets_and_retrieve_destination()
    {
        $strategy = new TestMergeStrategy($this->manager);

        $this->assertEquals('foo', $strategy->destination('foo'));
    }

    /** @test */
    public function strategy_gets_merge_contents()
    {
        $this->manager->mergeFilesystem()->write('foo/foo.txt', 'Hello World');

        $strategy = new TestMergeStrategy($this->manager);
        $strategy->destination('foo/foo.txt');

        $this->assertEquals('Hello World', $strategy->mergeContents());
    }

    /** @test */
    public function strategy_checks_integrity()
    {
        $fake_0 = UploadedFile::fake()->create('foo.txt', 2000);
        $fake_1 = UploadedFile::fake()->create('foo.txt', 2000);

        $this->manager->addChunk($fake_0, 0, 'foo');
        $this->manager->addChunk($fake_1, 1, 'foo');

        $strategy = new TestMergeStrategy($this->manager);
        $strategy->chunksFolder('chunks/foo');

        $this->assertTrue($strategy->checkIntegrity(0, 0));
        $this->assertFalse($strategy->checkIntegrity(1, 00));
        $this->assertFalse($strategy->checkIntegrity(0, 1));
        $this->assertFalse($strategy->checkIntegrity(1, 1));
    }
}
