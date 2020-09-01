<?php

namespace Jobtech\LaravelChunky\Events;

use Illuminate\Queue\SerializesModels;
use Jobtech\LaravelChunky\Strategies\Contracts\MergeStrategy;

class ChunksMerged
{
    use SerializesModels;

    /**
     * @var \Jobtech\LaravelChunky\Strategies\Contracts\MergeStrategy
     */
    private $strategy;

    /**
     * @var string
     */
    private $path;

    public function __construct(MergeStrategy $strategy, string $path)
    {
        $this->strategy = $strategy;
        $this->path = $path;
    }
}
