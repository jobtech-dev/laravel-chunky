<?php

namespace Jobtech\LaravelChunky\Contracts;

use Illuminate\Contracts\Filesystem\Filesystem;
use Jobtech\LaravelChunky\Chunk;
use Jobtech\LaravelChunky\Support\MergeFilesystem;

interface MergeManager
{
    /**
     * Retrieve the merge's filesystem depending on merge's disk setting.
     *
     * @return \Jobtech\LaravelChunky\Support\MergeFilesystem
     */
    public function mergeFilesystem(): MergeFilesystem;

    /**
     * Get the merge disk from settings.
     *
     * @return string|null
     */
    public function getMergeDisk(): ?string;

    /**
     * Get the merge disk from settings.
     *
     * @return string
     */
    public function getMergeFolder(): string;

    /**
     * Return the additional options for chunk file saving.
     */
    public function getMergeOptions(): array;

    /**
     * Move chunk origin into merge destination.
     *
     * @param string $destination
     * @param resource|null $origin
     * @param array $options
     * @return mixed
     */
    public function store(string $destination, $origin, $options = []);

    /**
     * @param string $path
     * @return string
     */
    public function destinationPath(string $path): string;
}
