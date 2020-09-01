<?php

namespace Jobtech\LaravelChunky\Strategies;

use FFMpeg\Format\Video\Ogg;
use FFMpeg\Format\Video\WebM;
use FFMpeg\Format\Video\WMV;
use FFMpeg\Format\Video\WMV3;
use FFMpeg\Format\Video\X264;
use Illuminate\Support\Arr;
use Jobtech\LaravelChunky\Strategies\Concerns\ChecksIntegrity;
use Jobtech\LaravelChunky\Strategies\Concerns\HandlesFFMpeg;
use Jobtech\LaravelChunky\Strategies\Contracts\MergeStrategy as MergeStrategyContract;
use ProtoneMedia\LaravelFFMpeg\FFMpeg\CopyFormat;

class VideoStrategy extends MergeStrategy
{
    use ChecksIntegrity,
        HandlesFFMpeg;

    public function merge(): MergeStrategyContract
    {
        $this->mergeWithFFMpeg();

        $this->deleteChunks($this->folder);

        return $this;
    }

    public function guessFormat()
    {
        $extension = strtolower(Arr::last(explode('.', $this->destination())));

        switch ($extension) {
            case 'oog':
                return new Ogg;
            case 'webm':
                return new WebM;
            case 'wmv':
                return new WMV;
            case 'wmv3':
                return new WMV3;
            case 'mp4':
                return new X264;
            default:
                return new CopyFormat;
        }
    }
}
