<?php

namespace Jobtech\LaravelChunky\Support;

use Illuminate\Container\Container;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\ForwardsCalls;
use League\Flysystem\Adapter\Local;

abstract class FileSystem
{
    use ForwardsCalls;

    /** @var \Illuminate\Contracts\Filesystem\Factory */
    private Factory $filesystem;

    /** @var string|null */
    protected ?string $disk = null;

    /** @var string|null */
    protected ?string $folder = null;

    public function __construct(Factory $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * @return \Illuminate\Contracts\Filesystem\Factory
     */
    public function filesystem(): Factory
    {
        return $this->filesystem;
    }

    /**
     * Check if filesystem is using local adapter.
     *
     * @return bool
     */
    public function isLocal(): bool
    {
        $driver = $this->filesystem()
            ->disk($this->disk)
            ->getDriver()
            ->getAdapter();

        return $driver instanceof Local;
    }

    /**
     * @param string $folder
     * @return bool
     */
    public function makeDirectory(string $folder): bool
    {
        if (! Str::startsWith($folder, $this->folder)) {
            $folder = $this->folder.$folder;
        }

        return $this->filesystem->disk($this->disk)->makeDirectory($folder);
    }

    /**
     * Disk getter and setter.
     *
     * @param string|null $disk
     * @return string|null
     */
    abstract public function disk($disk = null): ?string;

    /**
     * Folder getter and setter.
     *
     * @param string|null $folder
     * @return string|null
     */
    abstract public function folder($folder = null): ?string;

    public static function instance(array $config): FileSystem
    {
        $filesystem = Container::getInstance()->make(get_called_class());

        $filesystem->disk(Arr::get($config, 'disk'));
        $filesystem->folder(Arr::get($config, 'folder'));

        return $filesystem;
    }
}
