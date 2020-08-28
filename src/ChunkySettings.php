<?php

namespace Jobtech\LaravelChunky;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Jobtech\LaravelChunky\Merge\MergeSettings;

class ChunkySettings
{
    /** @var int */
    const INDEX_ZERO = 0;

    /** @var int */
    const INDEX_ONE = 1;

    /** @var MergeSettings|null */
    private static $merge_settings;

    /**
     * @var array
     */
    private $config;

    public function __construct(Repository $config)
    {
        $this->config = $config->get('chunky');
    }

    /**
     * Retrieve the chunky configurations.
     *
     * @return array
     */
    public function config() : array
    {
        return $this->config;
    }

    /**
     * Retrieve the default chunks disk.
     *
     * @return string|null
     */
    public function chunksDisk() : ?string
    {
        return Arr::get($this->config, 'disks.chunks.disk');
    }

    /**
     * Retrieve the chunks destination folder.
     *
     * @return string
     */
    public function chunksFolder() : string
    {
        $folder = Arr::get($this->config, 'disks.chunks.folder');

        if($folder === null) {
            return '';
        } else if(!Str::endsWith($folder, '/')) {
            $folder .= DIRECTORY_SEPARATOR;
        }

        return $folder;
    }

    /**
     * Retrieve the default merge file disk.
     *
     * @return string|null
     */
    public function mergeDisk() : ?string
    {
        return Arr::get($this->config, 'disks.merge.disk');
    }

    /**
     * Retrieve the merge file destination folder.
     *
     * @return string
     */
    public function mergeFolder() : string
    {
        $folder = Arr::get($this->config, 'disks.merge.folder');

        if($folder === null) {
            return '';
        } else if(!Str::endsWith($folder, '/')) {
            $folder .= DIRECTORY_SEPARATOR;
        }

        return $folder;
    }

    /**
     * Retrieve the default index value for chunks.
     *
     * @return int
     */
    public function defaultIndex() : int {
        return Arr::get($this->config, 'index', self::INDEX_ZERO)
            ?: self::INDEX_ZERO;
    }

    /**
     * Retrieve the additional options for chunk store.
     *
     * @return array
     */
    public function additionalChunksOptions() : array
    {
        return Arr::get($this->config, 'options.chunks', []);
    }

    /**
     * Retrieve the additional options for merge store.
     *
     * @return array
     */
    public function additionalMergeOptions() : array
    {
        return Arr::get($this->config, 'options.merge', []);
    }

    /**
     * Retrieve the auto merge option.
     *
     * @return bool
     */
    public function autoMerge() : bool
    {
        return Arr::get($this->config, 'auto_merge', false);
    }
}