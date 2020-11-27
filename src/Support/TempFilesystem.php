<?php

namespace Jobtech\LaravelChunky\Support;

class TempFilesystem extends Filesystem
{
    private array $temp_files = [];

    /**
     * @param string $path
     * @return resource|null
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function readFile(string $path)
    {
        return $this->filesystem()->disk($this->disk())->readStream($this->path($path));
    }

    /**
     * @param string $path
     * @param string|resource $resource
     * @param array  $options
     *
     * @throws \Illuminate\Contracts\Filesystem\FileExistsException
     * @return string
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
