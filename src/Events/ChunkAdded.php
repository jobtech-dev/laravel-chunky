<?php

namespace Jobtech\LaravelChunky\Events;

use Jobtech\LaravelChunky\Chunk;
use Illuminate\Queue\SerializesModels;

class ChunkAdded
{
    use SerializesModels;

    private Chunk $chunk;

    public function __construct(Chunk $chunk)
    {
        $this->chunk = $chunk;
    }
}
