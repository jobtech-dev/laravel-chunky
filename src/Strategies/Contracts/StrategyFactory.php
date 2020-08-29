<?php

namespace Jobtech\LaravelChunky\Strategies\Contracts;

interface StrategyFactory
{
    /**
     * Retrieve the default strategy.
     *
     * @param \Jobtech\LaravelChunky\Contracts\ChunksManager|null $manager
     *
     * @return \Jobtech\LaravelChunky\Strategies\Contracts\MergeStrategy
     */
    public function default($manager = null): MergeStrategy;

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
     * @param string                                              $mime_type
     * @param \Jobtech\LaravelChunky\Contracts\ChunksManager|null $manager
     *
     * @return MergeStrategy
     */
    public function buildFrom(string $mime_type, $manager = null): MergeStrategy;
}
