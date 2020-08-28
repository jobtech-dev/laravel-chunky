<?php

namespace Jobtech\LaravelChunky\Merge\Strategies\Contracts;

use Jobtech\LaravelChunky\Contracts\ChunksManager;

interface StrategyFactory
{
    /**
     * Retrieve the default strategy.
     *
     * @param \Jobtech\LaravelChunky\Contracts\ChunksManager $manager
     *
     * @return \Jobtech\LaravelChunky\Merge\Strategies\Contracts\MergeStrategy
     */
    public function default(ChunksManager $manager): MergeStrategy;

    /**
     * Retrieve the mapped mime types.
     *
     * @return array
     */
    public function mimeTypes(): array;

    /**
     * Retrieve the mapped strategies.
     *
     * @return array
     */
    public function strategies(): array;

    /**
     * Retrieve strategy instance from given mime type.
     *
     * @param string                                         $mime_type
     * @param \Jobtech\LaravelChunky\Contracts\ChunksManager $manager
     *
     * @return MergeStrategy
     */
    public function buildFrom(string $mime_type, ChunksManager $manager): MergeStrategy;
}
