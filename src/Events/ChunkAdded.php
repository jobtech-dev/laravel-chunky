<?php

namespace Jobtech\LaravelChunky\Events;

use Illuminate\Queue\SerializesModels;
use Jobtech\LaravelChunky\Chunk;

class ChunkAdded
{
    use SerializesModels;

    private Chunk $chunk;

    public function __construct(Chunk $chunk)
    {
        $this->chunk = $chunk;
    }
}
