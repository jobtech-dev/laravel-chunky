<?php

namespace Jobtech\LaravelChunky\Strategies;

use FFMpeg\Format\Audio\Aac;
use FFMpeg\Format\Audio\Flac;
use FFMpeg\Format\Audio\Mp3;
use FFMpeg\Format\Audio\Vorbis;
use FFMpeg\Format\Audio\Wav;
use Illuminate\Support\Arr;
use Jobtech\LaravelChunky\Strategies\Concerns\ChecksIntegrity;
use Jobtech\LaravelChunky\Strategies\Concerns\HandlesFFMpeg;
use Jobtech\LaravelChunky\Strategies\Contracts\MergeStrategy as MergeStrategyContract;
use ProtoneMedia\LaravelFFMpeg\FFMpeg\CopyFormat;

class AudioStrategy extends MergeStrategy
{
    use ChecksIntegrity,
        HandlesFFMpeg;

    public function merge(): MergeStrategyContract
    {
        $this->mergeWithFFMpeg();

        $this->deleteChunks($this->folder);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function guessFormat()
    {
        $extension = strtolower(Arr::last(explode('.', $this->destination())));

        switch ($extension) {
            case 'aac':
                return new Aac;
            case 'flac':
                return new Flac;
            case 'mp3':
                return new Mp3;
            case 'oog':
                return new Vorbis;
            case 'wav':
                return new Wav;
            default:
                return new CopyFormat;
        }
    }
}
