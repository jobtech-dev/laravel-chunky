<?php

namespace Jobtech\LaravelChunky\Tests\Unit\Strategies\Stubs;

use Jobtech\LaravelChunky\Strategies\Concerns\ChecksIntegrity;
use Jobtech\LaravelChunky\Strategies\MergeStrategy;
use Jobtech\LaravelChunky\Strategies\Contracts\MergeStrategy as MergeStrategyContract;

class TestMergeStrategy extends MergeStrategy
{
    use ChecksIntegrity;

    /**
     * {@inheritdoc}
     */
    public function merge() : MergeStrategyContract
    {
        return $this;
    }
}
