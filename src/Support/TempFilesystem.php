<?php

namespace Jobtech\LaravelChunky\Support;

use Illuminate\Contracts\Filesystem\FileExistsException;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class TempFilesystem extends Filesystem
{
    private array $temp_files = [];

    /**
     * @param string $path
     *
     * @return resource|null
     *
     * @throws FileNotFoundException
     */
    public function readFile(string $path)
    {
        return $this->filesystem()->disk($this->disk())->readStream($this->path($path));
    }

    /**
     * @param string          $path
     * @param resource|string $resource
     * @param array           $options
     *
     * @return string
     *
     * @throws FileExistsException
     */
    public function store(string $path, $resource = '', $options = []): string
    {
        $path = $this->path($path);
        $this->filesystem()->disk($this->disk())->writeStream($path, $resource, $options);
        $this->temp_files[] = $path;

        return $path;
    }

    public function clean()
    {
        foreach ($this->temp_files as $file) {
            $this->filesystem()->disk($this->disk())->delete($file);
        }
    }
}
