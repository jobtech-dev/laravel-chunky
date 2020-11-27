<?php

namespace Jobtech\LaravelChunky\Support;

use Jobtech\LaravelChunky\Events\MergeAdded;

class MergeFilesystem extends Filesystem
{
    /**
     * Write the origin stream into destination.
     *
     * @param string $destination
     * @param resource|null $origin
     * @param array $options
     *
     * @throws \Illuminate\Contracts\Filesystem\FileExistsException
     *
     * @return string
     */
    public function store(string $destination, $origin, $options = []): string
    {
        $destination = $this->path($destination);

        if ($this->filesystem()->disk($this->disk)->writeStream($destination, $origin, $options)) {
            event(new MergeAdded($destination));
            return $destination;
        }

        return false;
    }
}
