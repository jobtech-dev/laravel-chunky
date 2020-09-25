<?php

namespace Jobtech\LaravelChunky\Strategies\Contracts;

interface StrategyFactory
{

    /**
     * Retrieve the mapped strategies.
     *
     * @return array
     */
    public function strategies(): array;

    /**
     * Retrieve the mapped mime types.
     *
     * @return array
     */
    public function mimeTypes(): array;

    /**
     * Retrieve the default strategy.
     *
     * @return mixed
     */
    public function default();


    /**
     * Retrieve strategy instance from given mime type.
     *
     * @param string $mime_type
     * @param \Jobtech\LaravelChunky\Contracts\ChunksManager|null $chunksManager
     * @param \Jobtech\LaravelChunky\Contracts\MergeManager|null $mergeManager
     *
     * @return mixed
     */
    public function buildFrom(string $mime_type, $chunksManager = null, $mergeManager = null);
}
