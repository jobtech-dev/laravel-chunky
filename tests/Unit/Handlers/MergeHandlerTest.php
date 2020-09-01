<?php

namespace Jobtech\LaravelChunky\Tests\Unit\Handlers;

use Jobtech\LaravelChunky\Handlers\MergeHandler;
use Jobtech\LaravelChunky\Strategies\Contracts\MergeStrategy;
use Jobtech\LaravelChunky\Strategies\FlysystemStrategy;
use Jobtech\LaravelChunky\Strategies\VideoStrategy;
use Jobtech\LaravelChunky\Tests\TestCase;

class MergeHandlerTest extends TestCase
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
    public function handler_sets_and_retrieves_strategy()
    {
        $mock = $this->mock(MergeStrategy::class);

        $handler = new MergeHandler($this->manager);

        $this->assertNull($handler->strategy());
        $this->assertEquals($mock, $handler->strategy($mock));
    }

    /** @test */
    public function handler_forward_call_to_strategy_methods()
    {
        $mock = $this->mock(MergeStrategy::class, function ($mock) {
            $mock->shouldReceive('manager')
                ->once()
                ->andReturn($this->manager);

            $mock->shouldReceive('chunksFolder')
                ->once()
                ->with('foo')
                ->andReturn('foo');

            $mock->shouldReceive('destination')
                ->once()
                ->with('foo')
                ->andReturn('foo');

            $mock->shouldReceive('checkIntegrity')
                ->once()
                ->with(100, 100)
                ->andReturn(true);

            $mock->shouldReceive('merge')
                ->once()
                ->andReturnSelf();
        });

        $handler = new MergeHandler($this->manager);

        $this->assertEquals($mock, $handler->strategy($mock));
        $this->assertEquals($this->manager, $handler->manager());
        $this->assertEquals('foo', $handler->chunksFolder('foo'));
        $this->assertEquals('foo', $handler->destination('foo'));
        $this->assertTrue($handler->checkIntegrity(100, 100));
        $this->assertInstanceOf(MergeStrategy::class, $handler->merge());
    }

    /** @test */
    public function handler_retrieves_default_strategy()
    {
        $strategy_1 = MergeHandler::strategyBy('application/json');
        $strategy_2 = MergeHandler::strategyBy('application/*');
        $strategy_3 = MergeHandler::strategyBy('*/*');
        $strategy_4 = MergeHandler::strategyBy('foo');

        $this->assertInstanceOf(FlysystemStrategy::class, $strategy_1);
        $this->assertInstanceOf(FlysystemStrategy::class, $strategy_2);
        $this->assertInstanceOf(FlysystemStrategy::class, $strategy_3);
        $this->assertInstanceOf(FlysystemStrategy::class, $strategy_4);
    }

    /** @test */
    public function handler_retrieves_video_strategy()
    {
        $strategy_1 = MergeHandler::strategyBy('video/mp4');
        $strategy_2 = MergeHandler::strategyBy('video/*');

        $this->assertInstanceOf(VideoStrategy::class, $strategy_1);
        $this->assertInstanceOf(VideoStrategy::class, $strategy_2);
    }

    /** @test */
    public function handler_creates_new_instace()
    {
        $this->manager->chunksFilesystem()->makeDirectory('chunks/foo');

        $handler = MergeHandler::create(
            'foo',
            'foo/foo.ext',
            'video/*'
        );

        $this->assertEquals($this->manager, $handler->manager());
        $this->assertEquals('foo', $handler->chunksFolder());
        $this->assertInstanceOf(VideoStrategy::class, $handler->strategy());
        $this->assertEquals('foo/foo.ext', $handler->destination());
    }
}
