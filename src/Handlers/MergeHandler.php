<?php

namespace Jobtech\LaravelChunky\Handlers;

use Jobtech\LaravelChunky\Chunk;
use Illuminate\Container\Container;
use Jobtech\LaravelChunky\Jobs\MergeChunks;
use Illuminate\Support\Traits\ForwardsCalls;
use Jobtech\LaravelChunky\Events\ChunksMerged;
use Jobtech\LaravelChunky\Support\TempFilesystem;
use Jobtech\LaravelChunky\Contracts\ChunkyManager;
use Jobtech\LaravelChunky\Exceptions\ChunkyException;
use Illuminate\Contracts\Filesystem\FileExistsException;
use Jobtech\LaravelChunky\Http\Requests\AddChunkRequest;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Jobtech\LaravelChunky\Exceptions\ChunksIntegrityException;
use Jobtech\LaravelChunky\Contracts\MergeHandler as MergeHandlerContract;

/**
 * @method \Jobtech\LaravelChunky\ChunkySettings           settings()
 * @method \Jobtech\LaravelChunky\Support\ChunksFilesystem chunksFilesystem()
 * @method \Jobtech\LaravelChunky\Support\MergeFilesystem  mergeFilesystem()
 * @method array                                           mergeOptions()
 * @method \Illuminate\Support\Collection                  listChunks(?string $folder = null)
 * @method bool                                            deleteChunks(string $folder)
 * @method bool                                            isValidChunksFolder(string $folder)
 * @method bool                                            checkChunksIntegrity(string $folder, int $chunk_size, int $total_size)
 */
class MergeHandler implements MergeHandlerContract
{
    use ForwardsCalls;

    private ?ChunkyManager $manager;

    private TempFilesystem $temp_filesystem;

    public function __construct(?ChunkyManager $manager = null)
    {
        $this->manager = $manager;
        $this->temp_filesystem = Container::getInstance()->make(TempFilesystem::class);
    }

    public function __call($method, $parameters)
    {
        if (!method_exists($this, $method)) {
            return $this->forwardCallTo($this->manager(), $method, $parameters);
        }

        return $this->{$method}(...$parameters);
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    public function setManager(ChunkyManager $manager): MergeHandler
    {
        $this->manager = $manager;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    public function manager(): ChunkyManager
    {
        if ($this->manager === null) {
            $this->manager = Container::getInstance()->make('chunky');
        }

        return $this->manager;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatchMerge(AddChunkRequest $request, string $folder)
    {
        $merge_path = $request->fileInput()->getClientOriginalName();
        $chunk_size = $request->chunkSizeInput();
        $total_size = $request->totalSizeInput();

        if (empty($connection = $this->settings()->connection())) {
            if (!$this->checkChunksIntegrity($folder, $chunk_size, $total_size)) {
                throw new ChunksIntegrityException('Chunks total file size doesnt match with original file size');
            }

            return $this->merge($folder, $merge_path);
        }

        return MergeChunks::dispatch(
            $folder,
            $merge_path,
            $chunk_size,
            $total_size
        )->onConnection($connection)
            ->onQueue($this->settings()->queue());
    }

    /**
     * {@inheritdoc}
     */
    public function merge(string $chunks_folder, string $merge_path): string
    {
        // Check chunks folder
        if (!$this->isValidChunksFolder($chunks_folder)) {
            throw new ChunkyException("Invalid chunks folder {$chunks_folder}");
        }

        $path = $this->concatenate($chunks_folder, $merge_path);

        // Final check and cleanup
        if (!$path) {
            throw new ChunkyException('An error occurred while moving merge to destination');
        }

        $this->deleteChunks($chunks_folder);

        event(new ChunksMerged($path));

        return $path;
    }

    /**
     * {@inheritdoc}
     */
    public static function instance(?ChunkyManager $manager = null): MergeHandlerContract
    {
        return new static($manager);
    }

    /**
     * @param string $folder
     * @param string $target
     *
     * @return string
     *
     * @throws ChunkyException|FileExistsException|FileNotFoundException
     */
    private function concatenate(string $folder, string $target): string
    {
        $chunks = $this->listChunks($folder)->map(fn (Chunk $chunk) => $chunk->getPath())->all();
        if ($chunks === []) {
            throw new ChunkyException("No chunks found in {$folder}");
        }

        $chunksFilesystem = $this->chunksFilesystem();
        $stream = $chunksFilesystem->readConcatenatedStream($chunks);

        try {
            if ($chunksFilesystem->isLocal()) {
                return $this->mergeFilesystem()->store($target, $stream, $this->mergeOptions());
            }

            return $this->storeStagedMerge($target, $stream);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    /**
     * @param resource $stream
     * @param string   $target
     */
    private function storeStagedMerge(string $target, $stream): string
    {
        $temporaryFilesystem = clone $this->temp_filesystem;
        try {
            $temporaryPath = $temporaryFilesystem->store('merge-'.bin2hex(random_bytes(8)), $stream);
            $stagedStream = $temporaryFilesystem->readFile($temporaryPath);
            if (!is_resource($stagedStream)) {
                throw new ChunkyException('Unable to read the staged merge');
            }

            try {
                return $this->mergeFilesystem()->store($target, $stagedStream, $this->mergeOptions());
            } finally {
                if (is_resource($stagedStream)) {
                    fclose($stagedStream);
                }
            }
        } finally {
            $temporaryFilesystem->clean();
        }
    }
}
