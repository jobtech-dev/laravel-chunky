<?php

namespace Jobtech\LaravelChunky\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static int lastIndexFrom(\Jobtech\LaravelChunky\Http\Requests\AddChunkRequest $request)
 * @method static bool isLastIndex(\Jobtech\LaravelChunky\Http\Requests\AddChunkRequest $request)
 * @method static \Jobtech\LaravelChunky\ChunkySettings settings()
 * @method static \Jobtech\LaravelChunky\Contracts\ChunkyManager setChunksFilesystem(string $disk, string $folder)
 * @method static \Jobtech\LaravelChunky\Support\ChunksFilesystem chunksFilesystem()
 * @method static \Jobtech\LaravelChunky\Contracts\ChunkyManager setMergeFilesystem(string $disk, string $folder)
 * @method static \Jobtech\LaravelChunky\Support\MergeFilesystem mergeFilesystem()
 * @method static \Jobtech\LaravelChunky\Contracts\MergeHandler mergeHandler()
 * @method static string|null chunksDisk()
 * @method static string|null mergeDisk()
 * @method static string chunksFolder()
 * @method static string mergeFolder()
 * @method static array chunksOptions()
 * @method static array mergeOptions()
 * @method static \Illuminate\Support\Collection listChunks(?string $folder = null)
 * @method static resource|null readChunk($chunk)
 * @method static \Jobtech\LaravelChunky\Chunk addChunk(\Illuminate\Http\UploadedFile $file, int $index, string $folder)
 * @method static bool deleteChunks(string $folder)
 * @method static bool isValidChunksFolder(string $folder)
 * @method static bool checkChunks(string $folder, int $index)
 * @method static bool checkChunksIntegrity(string $folder, int $chunk_size, int $total_size)
 * @method static \Jobtech\LaravelChunky\Chunk handle(\Jobtech\LaravelChunky\Http\Requests\AddChunkRequest $request, ?string $folder = null)
 * @method static string merge(string $chunks_folder, string $merge_path)
 */
class Chunky extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'chunky';
    }
}
