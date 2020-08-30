<?php

namespace Jobtech\LaravelChunky\Contracts;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Jobtech\LaravelChunky\Chunk;

interface ChunksManager
{
    /**
     * Retrieve the chunks' filesystem depending on chunk's disk setting.
     *
     * @return \Illuminate\Contracts\Filesystem\Filesystem
     */
    public function chunksFilesystem(): Filesystem;

    /**
     * Retrieve the merge's filesystem depending on merge's disk setting.
     *
     * @return \Illuminate\Contracts\Filesystem\Filesystem
     */
    public function mergeFilesystem(): Filesystem;

    /**
     * Get the chunks disk from settings.
     *
     * @return string|null
     */
    public function getChunksDisk(): ?string;

    /**
     * Get the merge disk from settings.
     *
     * @return string|null
     */
    public function getMergeDisk(): ?string;

    /**
     * Get the chunks folder from settings.
     *
     * @return string
     */
    public function getChunksFolder(): string;

    /**
     * Get the merge disk from settings.
     *
     * @return string
     */
    public function getMergeFolder(): string;

    /**
     * Return the additional options for chunk file saving.
     */
    public function getChunksOptions(): array;

    /**
     * Return the additional options for chunk file saving.
     */
    public function getMergeOptions(): array;

    /**
     * Builds an ordered chunks collection.
     *
     * @param string $folder
     *
     * @return \Illuminate\Support\Collection
     */
    public function chunks(string $folder): Collection;

    /**
     * Check if the given chunk is the missing one or breaks the chunks integrity.
     *
     * @param string $folder
     * @param int    $index
     *
     * @return bool
     */
    public function checkChunkIntegrity(string $folder, int $index): bool;

    /**
     * Add a chunk from uploaded file. This method will also check the integrity of the
     * chunks folder.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param int                           $index
     * @param string                        $folder
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     *
     * @return \Jobtech\LaravelChunky\Chunk
     */
    public function addChunk(UploadedFile $file, int $index, string $folder): Chunk;
}
