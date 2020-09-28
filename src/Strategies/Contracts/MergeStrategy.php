<?php

namespace Jobtech\LaravelChunky\Strategies\Contracts;

use Jobtech\LaravelChunky\Contracts\ChunksManager;
use Jobtech\LaravelChunky\Contracts\MergeManager;
use Jobtech\LaravelChunky\Exceptions\StrategyException;

interface MergeStrategy
{
    /**
     * @param string|null $destination
     * @return mixed
     */
    public function destination($destination = null) : string;

    /**
     * Set and retrieve or retrieve the chunks manager.
     *
     * @param ChunksManager|null $manager
     *
     * @throws StrategyException
     *
     * @return ChunksManager
     */
    public function chunksManager($manager = null): ChunksManager;

    /**
     * Set and retrieve or retrieve the chunks manager.
     *
     * @param MergeManager|null $manager
     *
     * @throws StrategyException
     *
     * @return MergeManager
     */
    public function mergeManager($manager = null): MergeManager;

    /**
     * Set and retrieve or retrieve the chunks folder.
     *
     * @param string|null $folder
     *
     * @throws StrategyException
     *
     * @return string
     */
    public function chunksFolder($folder = null): string;

    /**
     * Handles the merge of the chunks into a single file and returns the final path.
     *
     * @throws StrategyException
     *
     * @return string
     */
    public function merge(): string;

}
