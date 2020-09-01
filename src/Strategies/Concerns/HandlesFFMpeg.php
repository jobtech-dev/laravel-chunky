<?php

namespace Jobtech\LaravelChunky\Strategies\Concerns;

use Illuminate\Support\Arr;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;

trait HandlesFFMpeg
{
    public function mergeWithFFMpeg(): void
    {
        $exporter = FFMpeg::fromDisk($this->manager->chunksFilesystem())
            ->open($this->mapChunksToArray())
            ->export()
            ->inFormat($this->guessFormat())
            ->concatWithoutTranscoding();

        if (! empty($visibility = $this->visibility())) {
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

    /**
     * Guess format from destination file extension.
     *
     * @return mixed
     */
    abstract public function guessFormat();
}
