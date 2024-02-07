<?php

namespace Jobtech\LaravelChunky\Tests\Unit\Concerns;

use Jobtech\LaravelChunky\ChunkyManager;
use Jobtech\LaravelChunky\ChunkySettings;
use Jobtech\LaravelChunky\Tests\TestCase;
use Jobtech\LaravelChunky\Http\Requests\AddChunkRequest;

/**
 * @internal
 */
class ChunkyRequestHelpersTest extends TestCase
{
    /**
     * @var ChunkyManager
     */
    private $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = new ChunkyManager(new ChunkySettings(
            $this->app->make('config')
        ));
    }

    public static function indexProvider(): array
    {
        return [
            [2000, 2000, 1],
            [2000, 4000, 2],
            [2000, 4001, 3],
            [2000, 5000, 3],
        ];
    }

    public static function lastIndexProvider(): array
    {
        return [
            [ChunkySettings::INDEX_ZERO, 2000, 2000, true],
            [ChunkySettings::INDEX_ZERO, 2000, 4000, false],
            [0, 2000, 2000, true],
            [0, 2000, 4000, false],
        ];
    }

    /** @test */
    public function managerReturns1OnTotalSizeLowerThanChunkSize()
    {
        $request = $this->mock(AddChunkRequest::class, function ($mock) {
            $mock->shouldReceive('totalSizeInput')
                ->once()
                ->andReturn(1);
            $mock->shouldReceive('chunkSizeInput')
                ->once()
                ->andReturn(2);
        });

        $this->assertEquals(1, $this->manager->lastIndexFrom($request));
    }

    /**
     * @test
     *
     * @dataProvider indexProvider
     *
     * @param       $chunk_size
     * @param       $total_size
     * @param mixed $result
     */
    public function managerRetrieveLastIndexFromRequest($chunk_size, $total_size, $result)
    {
        $request = $this->mock(AddChunkRequest::class, function ($mock) use ($chunk_size, $total_size) {
            $mock->shouldReceive('totalSizeInput')
                ->once()
                ->andReturn($total_size);
            $mock->shouldReceive('chunkSizeInput')
                ->once()
                ->andReturn($chunk_size);
        });

        $this->assertEquals($result, $this->manager->lastIndexFrom($request));
    }

    /**
     * @test
     *
     * @dataProvider lastIndexProvider
     *
     * @param $chunk_size
     * @param $total_size
     * @param $index
     * @param $result
     */
    public function managerCheckIfRequestIndexIsTheLastOne($index, $chunk_size, $total_size, $result)
    {
        $request = $this->mock(AddChunkRequest::class, function ($mock) use ($index, $chunk_size, $total_size) {
            $mock->shouldReceive('totalSizeInput')
                ->once()
                ->andReturn($total_size);
            $mock->shouldReceive('chunkSizeInput')
                ->once()
                ->andReturn($chunk_size);
            $mock->shouldReceive('indexInput')
                ->once()
                ->andReturn($index);
        });

        $this->assertEquals($result, $this->manager->isLastIndex($request));
    }
}
