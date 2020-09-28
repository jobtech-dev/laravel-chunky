<?php

namespace Jobtech\LaravelChunky\Strategies\Contracts;

interface StrategyFactory
{

    /**
     * Retrieve the default strategy.
     *
     * @return mixed
     */
    public function strategyInstance();


    /**
     * Retrieve strategy instance from given mime type.
     *
     * @param \Jobtech\LaravelChunky\Contracts\ChunksManager|null $chunksManager
     * @param \Jobtech\LaravelChunky\Contracts\MergeManager|null $mergeManager
     *
     * @return mixed
     */
    public function buildFrom($chunksManager = null, $mergeManager = null);
}
