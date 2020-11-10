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
        if (is_resource($origin)) {
            $origin = stream_get_contents($origin);
        }

        if ($this->filesystem()->disk($this->disk)->put($destination, $origin, $options)) {
            event(new MergeAdded($destination));

            return $destination;
        }

        return false;
    }
}
