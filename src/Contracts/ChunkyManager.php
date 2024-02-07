<?php

namespace Jobtech\LaravelChunky\Contracts;

use Jobtech\LaravelChunky\Chunk;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Jobtech\LaravelChunky\ChunkySettings;
use Jobtech\LaravelChunky\Support\MergeFilesystem;
use Jobtech\LaravelChunky\Support\ChunksFilesystem;
use Jobtech\LaravelChunky\Http\Requests\AddChunkRequest;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Container\BindingResolutionException;

/**
 * @method int  lastIndexFrom(AddChunkRequest $request)
 * @method bool isLastIndex(AddChunkRequest $request)
 */
interface ChunkyManager
{
    /**
     * @return ChunkySettings
     */
    public function settings(): ChunkySettings;

    /**
     * @param string $disk
     * @param string $folder
     *
     * @return \Jobtech\LaravelChunky\ChunkyManager
     *
     * @throws BindingResolutionException
     */
    public function setChunksFilesystem(string $disk, string $folder): ChunkyManager;

    /**
     * @return ChunksFilesystem
     */
    public function chunksFilesystem(): ChunksFilesystem;

    /**
     * @param string $disk
     * @param string $folder
     *
     * @return \Jobtech\LaravelChunky\ChunkyManager
     *
     * @throws BindingResolutionException
     */
    public function setMergeFilesystem(string $disk, string $folder): ChunkyManager;

    /**
     * @return MergeFilesystem
     */
    public function mergeFilesystem(): MergeFilesystem;

    /**
     * @return MergeHandler
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
     * @param bool        $temporary
     *
     * @return Collection
     */
    public function listChunks(?string $folder = null): Collection;

    /**
     * @param Chunk|string $chunk
     *
     * @return resource|null
     *
     * @throws FileNotFoundException
     */
    public function readChunk($chunk);

    /**
     * @param UploadedFile $file
     * @param int          $index
     * @param string       $folder
     *
     * @return Chunk
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
     * @param int    $index
     *
     * @return bool
     */
    public function checkChunks(string $folder, int $index): bool;

    /**
     * @param string $folder
     * @param int    $chunk_size
     * @param int    $total_size
     *
     * @return bool
     */
    public function checkChunksIntegrity(string $folder, int $chunk_size, int $total_size): bool;

    /**
     * @param AddChunkRequest $request
     * @param string|null     $folder
     *
     * @return Chunk
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
