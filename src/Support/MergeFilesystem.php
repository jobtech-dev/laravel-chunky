<?php

namespace Jobtech\LaravelChunky\Support;

class MergeFilesystem extends FileSystem
{
    /** {@inheritDoc} */
    function disk($disk = null): ?string
    {
        if(!empty($disk) && is_string($disk)) {
            $this->disk = $disk;
        }

        return  $this->disk;
    }

    /** {@inheritDoc} */
    function folder($folder = null): ?string
    {
        if(!empty($folder) && is_string($folder)) {
            $this->folder = $folder;
        }

        return $this->folder;
    }

    /**
     * @param string $path
     * @return string
     */
    public function destinationPath(string $path): string
    {
        return $this->folder.$path;
    }

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
        if($this->filesystem()->disk($this->disk)->writeStream($destination, $origin, $options)) {
            return $destination;
        }

        return false;
    }

    public function createTemporaryChunk($origin) {
        $this->filesystem()->disk();
    }
}