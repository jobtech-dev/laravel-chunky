<?php

namespace Jobtech\LaravelChunky\Contracts;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Jobtech\LaravelChunky\Chunk;
use Jobtech\LaravelChunky\Concerns\ManagerHelpers;
use Jobtech\LaravelChunky\Concerns\ChunkyRequestHelpers;
use Jobtech\LaravelChunky\Handlers\MergeHandler;
use Jobtech\LaravelChunky\Http\Requests\AddChunkRequest;
use Jobtech\LaravelChunky\Support\ChunksFilesystem;
use Jobtech\LaravelChunky\Support\MergeFilesystem;

/**
 * @mixin ManagerHelpers
 * @mixin ChunkyRequestHelpers
 */
interface ChunksManager
{
    /**
     * Retrieve the chunks' filesystem depending on chunk's disk setting.
     *
     * @return \Jobtech\LaravelChunky\Support\ChunksFilesystem
     */
    public function chunksFilesystem(): ChunksFilesystem;

    /**
     * Get the chunks disk from settings.
     *
     * @return string|null
     */
    public function getChunksDisk(): ?string;

    /**
     * Get the chunks folder from settings.
     *
     * @return string
     */
    public function getChunksFolder(): string;

    /**
     * Return the additional options for chunk file saving.
     */
    public function getChunksOptions(): array;

    /**
     * @param string $folder
     * @return bool
     */
    public function validFolder(string $folder): bool;

    /**
     * Move chunks contained in folder into temporary files
     *
     * @param string $folder
     * @return \Illuminate\Support\Collection
     */
    public function temporaryFiles(string $folder): Collection;

    /**
     * Builds an ordered chunks collection.
     *
     * @param string|null $folder
     *
     * @return \Illuminate\Support\Collection
     */
    public function chunks($folder = null): Collection;

    /**
     * Returns chunk as stream.
     *
     * @param Chunk|string $chunk
     * @return resource|null
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function chunk($chunk);

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

    /**
     * Delete all chunks folders and their content.
     *
     * @param string $folder
     * @return bool
     */
    public function deleteChunkFolder(string $folder): bool;

    /**
     * Delete all chunks folders and their content.
     *
     * @param null $output
     * @return bool
     */
    public function deleteAllChunks($output = null): bool;

    /**
     * Handles an add chunks request.
     *
     * @param \Jobtech\LaravelChunky\Http\Requests\AddChunkRequest $request
     * @param string|null                                          $folder
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     *
     * @return \Jobtech\LaravelChunky\Chunk
     */
    public function handle(AddChunkRequest $request, $folder = null): Chunk;

    /**
     * @param string $folder
     * @param string $destination
     * @param int $chunk_size
     * @param int $total_size
     */
    public function handleMerge(string $folder, string $destination, int $chunk_size, int $total_size);

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
     * Check the chunk's integrity for the given folder.
     *
     * @param string $folder
     * @param int $chunk_size
     * @param int $total_size
     *
     * @return bool
     */
    public function checkFilesIntegrity(string $folder, int $chunk_size, int $total_size): bool;
}
