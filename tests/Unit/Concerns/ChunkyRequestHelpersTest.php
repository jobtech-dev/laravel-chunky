<?php

namespace Jobtech\LaravelChunky\Tests\Unit\Concerns;

use Illuminate\Contracts\Filesystem\Factory;
use Jobtech\LaravelChunky\ChunksManager;
use Jobtech\LaravelChunky\ChunkySettings;
use Jobtech\LaravelChunky\Exceptions\ChunksIntegrityException;
use Jobtech\LaravelChunky\Http\Requests\AddChunkRequest;
use Jobtech\LaravelChunky\Tests\TestCase;

class ChunkyRequestHelpersTest extends TestCase
{
    /**
     * @var \Jobtech\LaravelChunky\ChunksManager
     */
    private $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = new ChunksManager(new ChunkySettings(
            $this->app->make('config')
        ));
    }

    public function indexProvider(): array
    {
        return [
            [2000, 2000, 1],
            [2000, 4000, 2],
            [2000, 4001, 3],
            [2000, 5000, 3],
        ];
    }

    public function lastIndexProvider(): array
    {
        return [
            [ChunkySettings::INDEX_ZERO, 2000, 2000, true],
            [ChunkySettings::INDEX_ZERO, 2000, 4000, false],
            [0, 2000, 2000, true],
            [0, 2000, 4000, false]
        ];
    }

    /** @test */
    public function manager_throws_exception_on_total_size_lower_than_chunk_size()
    {
        $request = $this->mock(AddChunkRequest::class, function ($mock) {
            $mock->shouldReceive('totalSizeInput')
               ->once()
               ->andReturn(1);
            $mock->shouldReceive('chunkSizeInput')
                ->once()
                ->andReturn(2);
        });

        $this->expectException(ChunksIntegrityException::class);

        $this->manager->lastIndexFrom($request);
    }

    /**
     * @test
     *
     * @dataProvider indexProvider
     *
     * @param $chunk_size
     * @param $total_size
     */
    public function manager_retrieve_last_index_from_request($chunk_size, $total_size, $result)
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
    public function manager_check_if_request_index_is_the_last_one($index, $chunk_size, $total_size, $result)
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
