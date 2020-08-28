<?php

namespace Jobtech\LaravelChunky\Merge\Strategies;

use Illuminate\Support\Arr;
use Jobtech\LaravelChunky\Merge\Strategies\Concerns\ChecksIntegrity;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;

class VideoStrategy extends MergeStrategy
{
    use ChecksIntegrity;

    public function merge()
    {
        $this->handleVideoMerge();

        $this->deleteChunks();

        return $this->mergeContents();
    }

    public function handleVideoMerge(): void
    {
        $exporter = FFMpeg::fromDisk($this->manager->chunksFilesystem())
            ->open($this->mapChunksToArray())
            ->export();

        $exporter->concatWithoutTranscoding();

        if (!empty($visibility = $this->visibility())) {
            $exporter->withVisibility($visibility);
        }

        $exporter->toDisk(
            $this->manager->getMergeDisk()
        )->save($this->destination);
    }

    /**
     * Retrieve visibility option.
     *
     * @return string|null
     */
    public function visibility(): ?string
    {
        return Arr::get(
            $this->manager->getMergeOptions(),
            'visibility'
        );
    }
}
