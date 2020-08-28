<?php

namespace Jobtech\LaravelChunky\Tests\Unit\Merge\Strategies\Stubs;

use Jobtech\LaravelChunky\Merge\Strategies\Concerns\ChecksIntegrity;
use Jobtech\LaravelChunky\Merge\Strategies\MergeStrategy;

class TestMergeStrategy extends MergeStrategy
{
    use ChecksIntegrity;

    /**
     * @inheritDoc
     */
    public function merge()
    {
        return true;
    }
}