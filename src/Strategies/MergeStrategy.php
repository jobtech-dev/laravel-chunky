<?php

namespace Jobtech\LaravelChunky\Strategies;

use Illuminate\Support\Traits\ForwardsCalls;
use Jobtech\LaravelChunky\Contracts\ChunksManager;
use Jobtech\LaravelChunky\Contracts\MergeManager;
use Jobtech\LaravelChunky\Exceptions\StrategyException;
use Jobtech\LaravelChunky\Strategies\Contracts\MergeStrategy as MergeStrategyContract;

abstract class MergeStrategy implements MergeStrategyContract
{
    use ForwardsCalls;

    /**
     * @var string
     */
    protected string $folder;

    /**
     * @var string
     */
    protected string $destination;

    /**
     * @var \Jobtech\LaravelChunky\Contracts\ChunksManager|null
     */
    protected ?ChunksManager $chunksManager;

    /**
     * @var \Jobtech\LaravelChunky\Contracts\MergeManager|null
     */
    protected ?MergeManager $mergeManager;

    /**
     * {@inheritdoc}
     */
    public function chunksManager($manager = null): ChunksManager
    {
        if ($manager instanceof ChunksManager) {
            $this->chunksManager = $manager;
        } elseif ($this->chunksManager === null) {
            throw new StrategyException('Chunks manager cannot be empty');
        }

        return $this->chunksManager;
    }

    /**
     * {@inheritdoc}
     */
    public function mergeManager($manager = null): MergeManager
    {
        if ($manager instanceof MergeManager) {
            $this->mergeManager = $manager;
        } elseif ($this->mergeManager === null) {
            throw new StrategyException('Merge manager cannot be empty');
        }

        return $this->mergeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function chunksFolder($folder = null): string
    {
        if (is_string($folder) && $this->chunksManager->validFolder($folder)) {
            $this->folder = $folder;
        } elseif (empty($this->folder) || ! $this->chunksManager->validFolder($this->folder)) {
            throw new StrategyException('Chunks folder cannot be empty');
        }

        return $this->folder;
    }
}
