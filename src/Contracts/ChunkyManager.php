<?php

namespace Jobtech\LaravelChunky\Contracts;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Jobtech\LaravelChunky\Chunk;
use Jobtech\LaravelChunky\ChunkySettings;
use Jobtech\LaravelChunky\Http\Requests\AddChunkRequest;
use Jobtech\LaravelChunky\Support\ChunksFilesystem;
use Jobtech\LaravelChunky\Support\MergeFilesystem;

/**
 * @method int lastIndexFrom(AddChunkRequest $request)
 * @method bool isLastIndex(AddChunkRequest $request)
 */
interface ChunkyManager
{
    /**
     * @return \Jobtech\LaravelChunky\ChunkySettings
     */
    public function settings(): ChunkySettings;

    /**
     * @param string $disk
     * @param string $folder
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     *
     * @return \Jobtech\LaravelChunky\ChunkyManager
     */
    public function setChunksFilesystem(string $disk, string $folder): ChunkyManager;

    /**
     * @return \Jobtech\LaravelChunky\Support\ChunksFilesystem
     */
    public function chunksFilesystem(): ChunksFilesystem;

    /**
     * @param string $disk
     * @param string $folder
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     *
     * @return \Jobtech\LaravelChunky\ChunkyManager
     */
    public function setMergeFilesystem(string $disk, string $folder): ChunkyManager;

    /**
     * @return \Jobtech\LaravelChunky\Support\MergeFilesystem
     */
    public function mergeFilesystem(): MergeFilesystem;

    /**
     * @return \Jobtech\LaravelChunky\Contracts\MergeHandler
     */
    public function mergeHandler(): MergeHandler;

    /**
     * @return string|null
     */
    public function chunksDisk(): ?string;

    /**
     * @return string|null
     */
    public function mergeDisk(): ?string;

    /**
     * @return string
     */
    public function chunksFolder(): string;

    /**
     * @return string
     */
    public function mergeFolder(): string;

    /**
     * @return array
     */
    public function chunksOptions(): array;

    /**
     * @return array
     */
    public function mergeOptions(): array;

    /**
     * @param string|null $folder
     * @param bool $temporary
     * @return \Illuminate\Support\Collection
     */
    public function listChunks(?string $folder = null): Collection;

    /**
     * @param Chunk|string $chunk
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     *
     * @return resource|null
     */
    public function readChunk($chunk);

    /**
     * @param \Illuminate\Http\UploadedFile $file
     * @param int $index
     * @param string $folder
     *
     * @return \Jobtech\LaravelChunky\Chunk
     */
    public function addChunk(UploadedFile $file, int $index, string $folder): Chunk;

    /**
     * @param string $folder
     *
     * @return bool
     */
    public function deleteChunks(string $folder): bool;

    /**
     * @param string $folder
     *
     * @return bool
     */
    public function isValidChunksFolder(string $folder): bool;

    /**
     * @param string $folder
     * @param int $index
     *
     * @return bool
     */
    public function checkChunks(string $folder, int $index): bool;

    /**
     * @param string $folder
     * @param int $chunk_size
     * @param int $total_size
     *
     * @return bool
     */
    public function checkChunksIntegrity(string $folder, int $chunk_size, int $total_size): bool;

    /**
     * @param \Jobtech\LaravelChunky\Http\Requests\AddChunkRequest $request
     * @param string|null $folder
     *
     * @return \Jobtech\LaravelChunky\Chunk
     */
    public function handle(AddChunkRequest $request, ?string $folder = null): Chunk;

    /**
     * @param string $chunks_folder
     * @param string $merge_path
     *
     * @return string
     */
    public function merge(string $chunks_folder, string $merge_path): string;
}
