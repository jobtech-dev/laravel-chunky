<?php

namespace Jobtech\LaravelChunky\Strategies;

use Illuminate\Container\Container;
use Illuminate\Support\Traits\ForwardsCalls;
use Jobtech\LaravelChunky\Contracts\ChunksManager;
use Jobtech\LaravelChunky\Exceptions\StrategyException;
use Jobtech\LaravelChunky\Strategies\Contracts\MergeStrategy as MergeStrategyContract;

/**
 * @mixin ChunksManager
 */
abstract class MergeStrategy implements MergeStrategyContract
{
    use ForwardsCalls;

    /**
     * @var string
     */
    protected $folder;

    /**
     * @var \Jobtech\LaravelChunky\Contracts\ChunksManager
     */
    protected $manager;

    /**
     * @var string
     */
    protected $destination;

    public function __construct($manager = null)
    {
        $this->manager = $manager ?: Container::getInstance()
            ->make('chunky');
    }

    /**
     * Retrieve chunks and map the paths into an array.
     *
     * @return array
     */
    protected function mapChunksToArray(): array
    {
        return $this->chunks(
            $this->folder
        )->map(function ($chunk) {
            return $chunk['path'];
        })->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function manager($manager = null): ChunksManager
    {
        if ($manager instanceof ChunksManager) {
            $this->manager = $manager;
        } elseif ($this->manager === null) {
            throw new StrategyException('Manager cannot be empty');
        }

        return $this->manager;
    }

    /**
     * {@inheritdoc}
     */
    public function chunksFolder($folder = null): string
    {
        if (is_string($folder) && $this->chunksFolderExists($this->folder)) {
            $this->folder = $folder;
        } elseif (empty($this->folder) || !$this->chunksFolderExists($this->folder)) {
            throw new StrategyException('Chunks folder cannot be empty');
        }

        return $this->folder;
    }

    /**
     * {@inheritdoc}
     */
    public function destination($destination = null): string
    {
        if (is_string($destination)) {
            $this->destination = $destination;
        } elseif (empty($this->destination)) {
            throw new StrategyException('Destination path cannot be empty');
        }

        return $this->destination;
    }

    /**
     * Retrieve the merge file contents.
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     *
     * @return string
     */
    public function mergeContents()
    {
        return $this->manager
            ->mergeFilesystem()
            ->get($this->destination);
    }

    public function __call($method, $parameters)
    {
        if (!method_exists($this, $method)) {
            return $this->forwardCallTo($this->manager, $method, $parameters);
        }

        return $this->{$method}(...$parameters);
    }

    /**
     * Create a new instance for the strategy.
     *
     * @param \Jobtech\LaravelChunky\Contracts\ChunksManager|null $manager
     *
     * @return static
     */
    public static function newInstance($manager = null)
    {
        return new static($manager);
    }
}
