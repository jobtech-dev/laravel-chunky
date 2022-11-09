<?php

namespace Jobtech\LaravelChunky\Support;

use Illuminate\Container\Container;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\ForwardsCalls;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\StorageAttributes;

abstract class Filesystem
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
     * @param string $path
     *
     * @return string
     */
    protected function path(string $path): string
    {
        if (! Str::startsWith($path, $this->folder)) {
            return $this->folder().$path;
        }

        return $path;
    }

    /**
     * @return \Illuminate\Contracts\Filesystem\Factory
     *
     * @codeCoverageIgnore
     */
    public function filesystem(): Factory
    {
        return $this->filesystem;
    }

    /**
     * Disk getter and setter.
     *
     * @param string|null $disk
     * @return string|null
     */
    public function disk($disk = null): ?string
    {
        if (! empty($disk) && is_string($disk)) {
            $this->disk = $disk;
        }

        return $this->disk;
    }

    /**
     * Folder getter and setter.
     *
     * @param string|null $folder
     * @return string|null
     */
    public function folder($folder = null): ?string
    {
        if (! empty($folder) && is_string($folder)) {
            $suffix = Str::endsWith($folder, DIRECTORY_SEPARATOR) ? '' : DIRECTORY_SEPARATOR;

            $this->folder = $folder.$suffix;
        }

        return $this->folder;
    }

    /**
     * @return bool
     *
     * @codeCoverageIgnore
     */
    public function isLocal(): bool
    {
        $adapter = $this->filesystem()
            ->disk($this->disk)
            ->getAdapter();

        return $adapter instanceof LocalFilesystemAdapter;
    }

    /**
     * @param string $path
     * @return bool
     */
    public function exists(string $path): bool
    {
        return $this->filesystem()
            ->disk($this->disk())
            ->exists($this->path($path));
    }

    /**
     * Retrieve every chunks' folder.
     *
     * @return array
     */
    public function folders(): array
    {
        return $this->filesystem()->disk($this->disk())->listContents($this->folder(), false)
            ->filter(fn (StorageAttributes $attributes) => $attributes->isDir())
            ->sortByPath()
            ->map(fn (StorageAttributes $attributes) => $attributes->path())
            ->toArray();
    }

    /**
     * @param string|null $folder
     *
     * @return array
     */
    public function list($folder = ''): array
    {
        return $this->filesystem()->disk(
            $this->disk()
        )->files(
            $this->path($folder)
        );
    }

    /**
     * @param string $folder
     * @return bool
     */
    public function makeDirectory(string $folder): bool
    {
        return $this->filesystem->disk($this->disk)
            ->makeDirectory($this->path($folder));
    }

    /**
     * @param array $config
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @return mixed
     */
    public static function instance(array $config)
    {
        $filesystem = Container::getInstance()->make(get_called_class());

        $filesystem->disk(Arr::get($config, 'disk'));
        $filesystem->folder(Arr::get($config, 'folder'));

        return $filesystem;
    }

    public function __call($method, $parameters)
    {
        if (! method_exists($this, $method)) {
            return $this->forwardCallTo($this->filesystem(), $method, $parameters);
        }

        return $this->{$method}($parameters);
    }
}
