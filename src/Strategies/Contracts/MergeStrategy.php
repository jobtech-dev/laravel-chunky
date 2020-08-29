<?php

namespace Jobtech\LaravelChunky\Strategies\Contracts;

use Jobtech\LaravelChunky\Contracts\ChunksManager;
use Jobtech\LaravelChunky\Exceptions\StrategyException;
use Symfony\Component\HttpFoundation\File\File;

interface MergeStrategy
{
    /**
     * Set and retrieve or retrieve the chunks manager.
     *
     * @param ChunksManager|null $manager
     *
     * @throws StrategyException
     *
     * @return ChunksManager
     */
    public function manager($manager = null): ChunksManager;

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
     * Retrieve the destination file for merge.
     *
     * @param string|null $destination
     *
     * @throws StrategyException
     *
     * @return string
     */
    public function destination($destination = null): string;

    /**
     * Check the file size integrity for the chunks.
     *
     * @param int $chunk_size
     * @param int $total_size
     *
     * @return bool
     */
    public function checkIntegrity(int $chunk_size, int $total_size): bool;

    /**
     * Handles the merge of the chunks into a single file.
     *
     * @throws StrategyException
     *
     * @return mixed
     */
    public function merge();
}
