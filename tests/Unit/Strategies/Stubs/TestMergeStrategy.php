<?php

namespace Jobtech\LaravelChunky\Tests\Unit\Strategies\Stubs;

use Jobtech\LaravelChunky\Strategies\Concerns\ChecksIntegrity;
use Jobtech\LaravelChunky\Strategies\MergeStrategy;

class TestMergeStrategy extends MergeStrategy
{
    use ChecksIntegrity;

    /**
     * {@inheritdoc}
     */
    public function merge()
    {
        return true;
    }
}
