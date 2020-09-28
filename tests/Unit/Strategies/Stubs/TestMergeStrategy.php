<?php

namespace Jobtech\LaravelChunky\Tests\Unit\Strategies\Stubs;

use Jobtech\LaravelChunky\Strategies\Contracts\MergeStrategy as MergeStrategyContract;
use Jobtech\LaravelChunky\Strategies\MergeStrategy;

class TestMergeStrategy extends MergeStrategy implements MergeStrategyContract
{

    /**
     * {@inheritdoc}
     */
    public function merge(): string
    {
        return $this;
    }
}
