<?php

namespace Jobtech\LaravelChunky\Events;

use Jobtech\LaravelChunky\Chunk;
use Illuminate\Queue\SerializesModels;

class ChunkDeleted
{
    use SerializesModels;

    private Chunk $chunk;

    public function __construct(Chunk $chunk)
    {
        $this->chunk = $chunk;
    }
}
