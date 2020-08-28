<?php

namespace Jobtech\LaravelChunky\Tests\Unit\Merge\Strategies;

use Illuminate\Support\Facades\Storage;
use Jobtech\LaravelChunky\ChunksManager;
use Jobtech\LaravelChunky\Merge\Strategies\VideoStrategy;
use Jobtech\LaravelChunky\Tests\TestCase;

class VideoStrategyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = $this->app->make('chunky');
    }

    /** @test */
    public function strategy_retrieves_visibility_from_options()
    {
        $mock = $this->mock(ChunksManager::class, function ($mock) {
            $mock->shouldReceive('getMergeOptions')
                ->once()
                ->andReturn([]);

            $mock->shouldReceive('getMergeOptions')
                ->once()
                ->andReturn([
                    'visibility' => 'public',
                ]);
        });

        $strategy = new VideoStrategy($mock);

        $this->assertNull($strategy->visibility());
        $this->assertEquals('public', $strategy->visibility());
    }

    /** @test */
    public function strategy_merges_chunks_without_transcode()
    {
        $strategy = new VideoStrategy($this->manager);
        $strategy->chunksFolder('resources/mp4');
        $strategy->destination('foo/sample.mp4');

        $strategy->merge();

        Storage::assertExists('foo/sample.mp4');
        Storage::assertMissing('resources/mp4/0_sample.mp4');
        Storage::assertMissing('resources/mp4/1_sample.mp4');
        Storage::assertMissing('resources/mp4/2_sample.mp4');
        Storage::assertMissing('resources/mp4');
    }

    /** @test */
    public function strategy_merges_chunks_with_transcode()
    {
        $strategy = new VideoStrategy($this->manager);
        $strategy->chunksFolder('resources/avi');
        $strategy->destination('foo/sample.mp4');

        $strategy->merge();

        Storage::assertExists('foo/sample.mp4');
        Storage::assertMissing('resources/avi/0_sample.avi');
        Storage::assertMissing('resources/avi/1_sample.avi');
        Storage::assertMissing('resources/avi/2_sample.avi');
        Storage::assertMissing('resources/avi');
    }
}
