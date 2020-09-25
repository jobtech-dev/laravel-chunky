<?php

namespace Jobtech\LaravelChunky\Handlers;

use Illuminate\Container\Container;
use Illuminate\Support\Traits\ForwardsCalls;
use Jobtech\LaravelChunky\Strategies\Contracts\MergeStrategy;
use Jobtech\LaravelChunky\Strategies\Contracts\StrategyFactory;

class MergeHandler
{
    /**
     * @var \Jobtech\LaravelChunky\Strategies\Contracts\MergeStrategy
     */
    private $strategy;

    /**
     * Set or retrieve the merge strategy of the handler.
     *
     * @param MergeStrategy|null $strategy
     *
     * @return \Jobtech\LaravelChunky\Strategies\Contracts\MergeStrategy|null
     */
    public function strategy($strategy = null): ?MergeStrategy
    {
        if ($strategy instanceof MergeStrategy) {
            $this->strategy = $strategy;
        }

        return $this->strategy;
    }
}
