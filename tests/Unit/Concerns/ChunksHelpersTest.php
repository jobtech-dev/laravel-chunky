<?php

namespace Jobtech\LaravelChunky\Tests\Unit\Concerns;

use Illuminate\Console\OutputStyle;
use Jobtech\LaravelChunky\Contracts\ChunksManager;
use Jobtech\LaravelChunky\Tests\TestCase;

class ChunksHelpersTest extends TestCase
{
    /**
     * @var \Jobtech\LaravelChunky\ChunksManager
     */
    private $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = $this->app->make(ChunksManager::class);
    }

    /** @test */
    public function manager_doesnt_create_progress_bar()
    {
        $progress_bar = $this->manager->hasProgressBar(null, 10);

        $this->assertNull($progress_bar);
    }

    /** @test */
    public function manager_creates_progress_bar()
    {
        $output = $this->mock(OutputStyle::class, function ($mock) {
            $mock->shouldReceive('createProgressBar')
                ->once()
                ->with(10)
                ->andReturn(null);
        });

        $result = $this->manager->hasProgressBar($output, 10);

        $this->assertNull($result);
    }
}
