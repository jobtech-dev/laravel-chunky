<?php

namespace Jobtech\LaravelChunky\Strategies;

use Illuminate\Support\Arr;
use Jobtech\LaravelChunky\Exceptions\StrategyException;
use Jobtech\LaravelChunky\Strategies\Concerns\ChecksIntegrity;
use Jobtech\LaravelChunky\Strategies\Contracts\MergeStrategy as MergeStrategyContract;
use Keven\Flysystem\Concatenate\Concatenate;

class FlysystemStrategy extends MergeStrategy
{
    use ChecksIntegrity;

    /**
     * Merge destination file with the chunk stream.
     *
     * @param array $chunks
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     *
     * @return bool
     */
    protected function mergeChunks(array $chunks): bool
    {
        $chunk = Arr::first($chunks);

        if (! $this->manager->chunksFilesystem()->concatenate($chunk, ...$chunks)) {
            throw new StrategyException('Unable to concatenate chunks');
        }

        return $this->manager->mergeFilesystem()
            ->put(
                $this->destination,
                $this->manager->chunksFilesystem()->get($chunk),
                $this->manager->getMergeOptions()
            );
    }

    /**
     * {@inheritdoc}
     */
    public function merge(): MergeStrategyContract
    {
        $this->manager->chunksFilesystem()
            ->addPlugin(new Concatenate());

        $this->mergeChunks(
            $this->mapChunksToArray()
        );

        $this->deleteChunks($this->folder);

        return $this;
    }
}
