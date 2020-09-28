<?php

namespace Jobtech\LaravelChunky\Strategies;

use Illuminate\Support\Facades\Storage;
use Jobtech\LaravelChunky\Chunk;
use Jobtech\LaravelChunky\Exceptions\StrategyException;

class FlysystemStrategy extends MergeStrategy
{
    /**
     * {@inheritdoc}
     */
    protected function mergeChunks(string $destination, array $chunks): bool
    {
        if (! $this->chunksManager->chunksFilesystem()->concatenate($destination, $chunks)) {
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
        $chunks = $this->chunksManager->chunks(
            $this->chunksFolder()
        )->map(function (Chunk $item) {
            return $item->getPath();
        });
        $chunk = $this->chunksManager->chunks(
            $this->chunksFolder()
        )->first();

        // Merge chunks
        $this->mergeChunks($chunk->getPath(), $chunks->toArray());

        // Move chunks to destination
        $origin = $this->chunksManager->chunk($chunk);
        $path = $this->mergeManager->store(
            $this->destination,
            $origin,
            $this->mergeManager->getMergeOptions()
        );

        if (! $path) {
            throw new StrategyException('An error occurred while moving merge to destination');
        }

        // Cleanup
        $this->chunksManager->deleteChunkFolder($this->folder);

        return $path;
    }

    public static function instance()
    {
        return new FlysystemStrategy;
    }
}
