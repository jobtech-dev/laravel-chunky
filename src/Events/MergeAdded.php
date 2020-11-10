<?php

namespace Jobtech\LaravelChunky\Events;

use Illuminate\Queue\SerializesModels;

class MergeAdded
{
    use SerializesModels;

    private string $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }
}
