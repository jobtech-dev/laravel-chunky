<?php

namespace Jobtech\LaravelChunky\Handlers;

use Jobtech\LaravelChunky\Strategies\Contracts\MergeStrategy;

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
