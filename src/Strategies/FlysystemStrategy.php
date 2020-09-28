<?php

namespace Jobtech\LaravelChunky\Strategies;

use Illuminate\Support\Arr;
use Jobtech\LaravelChunky\Exceptions\StrategyException;
use Jobtech\LaravelChunky\Strategies\Contracts\MergeStrategy as MergeStrategyContract;

class FlysystemStrategy extends MergeStrategy
{

    /**
     * {@inheritDoc}
     */
    protected function mergeChunks(string $chunk, array $chunks): bool
    {
        if (! $this->chunksManager->chunksFilesystem()->concatenate($chunk, $chunks)) {
            throw new StrategyException('Unable to concatenate chunks');
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function merge(): string
    {
        // Retrieve chunks
        $chunks = $this->chunksManager->temporaryFiles(
            $this->chunksFolder()
        )->values();
        $chunk = Arr::first($chunks);

        // Merge chunks
        $this->mergeChunks($chunk, $chunks->toArray());

        // Move chunks to destination
        $origin = $this->chunksManager->chunk($chunk);
        $path = $this->mergeManager->store(
            $this->destination,
            $origin,
            $this->mergeManager->getMergeOptions()
        );

        if(!$path) {
            throw new StrategyException("An error occurred while moving merge to destination");
        }

        // Cleanup
        $this->chunksManager->deleteChunkFolder($this->folder);

        return $path;
    }

    public static function instance() {
        return new FlysystemStrategy;
    }
}
