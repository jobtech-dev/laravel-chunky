<?php


namespace Jobtech\LaravelChunky\Merge\Strategies;

use Jobtech\LaravelChunky\Contracts\ChunksManager;
use Jobtech\LaravelChunky\Exceptions\StrategyException;
use Jobtech\LaravelChunky\Merge\Strategies\Contracts\MergeStrategy as MergeStrategyContract;

abstract class MergeStrategy implements MergeStrategyContract
{
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

    public function __construct(ChunksManager $manager) {
        $this->manager = $manager;
    }

    /**
     * Retrieve chunks and map the paths into an array.
     *
     * @return array
     */
    protected function mapChunksToArray() : array
    {
        return $this->manager->chunks(
            $this->folder
        )->map(function($chunk) {
            return $chunk['path'];
        })->toArray();
    }

    /**
     * {@inheritDoc}
     */
    public function manager($manager = null): ChunksManager
    {
        if($manager instanceof ChunksManager) {
            $this->manager = $manager;
        } else if($this->manager === null) {
            throw new StrategyException("Manager cannot be empty");
        }

        return $this->manager;
    }

    /**
     * {@inheritDoc}
     */
    public function chunksFolder($folder = null) : string {
        if(is_string($folder)) {
            $this->folder = $folder;
        } else if(empty($this->folder)) {
            throw new StrategyException("Chunks folder cannot be empty");
        }

        return $this->folder;
    }

    /**
     * {@inheritDoc}
     */
    public function destination($destination = null) : string {
        if(is_string($destination)) {
            $this->destination = $destination;
        } else if(empty($this->destination)) {
            throw new StrategyException("Destination path cannot be empty");
        }

        return $this->destination;
    }

    /**
     * Delete all chunks and, once empty, delete the folder.
     *
     * @return bool
     */
    public function deleteChunks() : bool
    {
        $files = $this->manager->chunksFilesystem()
            ->allFiles($this->folder);

        foreach($files as $file) {
            $deleted = $this->manager->chunksFilesystem()
                ->delete($file);

            if(!$deleted) {
                return false;
            }
        }

        return $this->manager->chunksFilesystem()
            ->deleteDirectory($this->folder);
    }

    /**
     * Retrieve the merge file contents.
     *
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function mergeContents()
    {
        return $this->manager
            ->mergeFilesystem()
            ->get($this->destination);
    }

    /**
     * Create a new instance for the strategy.
     *
     * @param $manager
     *
     * @return static
     */
    public static function newInstance($manager) {
        return new static($manager);
    }
}