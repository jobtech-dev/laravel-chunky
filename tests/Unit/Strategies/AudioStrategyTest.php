<?php

namespace Jobtech\LaravelChunky\Tests\Unit\Strategies;

use Illuminate\Support\Facades\Storage;
use Jobtech\LaravelChunky\ChunksManager;
use Jobtech\LaravelChunky\Strategies\AudioStrategy;
use Jobtech\LaravelChunky\Tests\TestCase;

class AudioStrategyTest extends TestCase
{
    /**
     * @var ChunksManager
     */
    private $manager;

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

        $strategy = new AudioStrategy($mock);

        $this->assertNull($strategy->visibility());
        $this->assertEquals('public', $strategy->visibility());
    }

    /** @test */
    public function strategy_merges_chunks_without_transcode()
    {
        $strategy = new AudioStrategy($this->manager);
        $strategy->chunksFolder('chunks/resources/mp3');
        $strategy->destination('foo/sample.mp3');

        $strategy->merge();

        Storage::assertExists('foo/sample.mp3');
        Storage::assertMissing('chunks/resources/mp3/0_sample.mp3');
        Storage::assertMissing('chunks/resources/mp3/1_sample.mp3');
        Storage::assertMissing('chunks/resources/mp3/2_sample.mp3');
        Storage::assertMissing('chunks/resources/mp3');
    }

    /** @test */
    public function strategy_merges_chunks_with_transcode()
    {
        $strategy = new AudioStrategy($this->manager);
        $strategy->chunksFolder('chunks/resources/mp3');
        $strategy->destination('foo/sample.wav');

        $strategy->merge();

        Storage::assertExists('foo/sample.wav');
        Storage::assertMissing('chunks/resources/mp3/0_sample.mp3');
        Storage::assertMissing('chunks/resources/mp3/1_sample.mp3');
        Storage::assertMissing('chunks/resources/mp3/2_sample.mp3');
        Storage::assertMissing('chunks/resources/mp3');
    }
}
