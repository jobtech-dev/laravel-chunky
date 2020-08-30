<?php

namespace Jobtech\LaravelChunky;

use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Jobtech\LaravelChunky\Concerns\ChunksHelpers;
use Jobtech\LaravelChunky\Concerns\ChunkyRequestHelpers;
use Jobtech\LaravelChunky\Contracts\ChunksManager as ChunksManagerContract;
use Jobtech\LaravelChunky\Exceptions\ChunksIntegrityException;
use Jobtech\LaravelChunky\Http\Requests\AddChunkRequest;
use Jobtech\LaravelChunky\Jobs\MergeChunks;
use Symfony\Component\HttpFoundation\File\File;

class ChunksManager implements ChunksManagerContract
{
    use ChunksHelpers;
    use ChunkyRequestHelpers;

    /**
     * @var \Illuminate\Contracts\Filesystem\Factory
     */
    private $filesystem;

    /**
     * @var \Jobtech\LaravelChunky\ChunkySettings
     */
    private $settings;

    public function __construct(Factory $filesystem, ChunkySettings $settings)
    {
        $this->filesystem = $filesystem;
        $this->settings = $settings;
    }

    /**
     * Build the full path for chunks' folder.
     *
     * @param string $folder
     *
     * @return string
     */
    private function fullPath(string $folder): string
    {
        return $this->getChunksFolder().$folder;
    }

    /**
     * Build the merge destination path.
     *
     * @param \Illuminate\Http\UploadedFile $file
     *
     * @return string
     */
    private function destinationPath(UploadedFile $file): string
    {
        return $this->getMergeFolder().$file->getFilename();
    }

    /**
     * Build chunks destination folder from file name.
     *
     * @param string $file
     *
     * @return string
     */
    private function buildChunkFolderFor(string $file)
    {
        return Str::slug($file);
    }

    /**
     * Dispatch a synchronous or asynchronous merge job depending on the settings.
     *
     * @param \Jobtech\LaravelChunky\Http\Requests\AddChunkRequest $request
     * @param string                                               $folder
     */
    private function dispatchMerge(AddChunkRequest $request, string $folder)
    {
        if (empty($connection = $this->settings->connection())) {
            MergeChunks::dispatchNow(
                $request,
                $folder,
                $this->destinationPath($request->fileInput())
            );
        } else {
            MergeChunks::dispatch(
                $request,
                $folder,
                $this->destinationPath($request->fileInput())
            )->onConnection($connection)
                ->onQueue($this->settings->queue());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function chunksFilesystem(): Filesystem
    {
        return $this->filesystem->disk(
            $this->getChunksDisk()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function mergeFilesystem(): Filesystem
    {
        return $this->filesystem->disk(
            $this->getMergeDisk()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getChunksDisk(): ?string
    {
        return $this->settings
            ->chunksDisk();
    }

    /**
     * {@inheritdoc}
     */
    public function getMergeDisk(): ?string
    {
        return $this->settings
            ->mergeDisk();
    }

    /**
     * {@inheritdoc}
     */
    public function getChunksFolder(): string
    {
        return $this->settings
            ->chunksFolder();
    }

    /**
     * {@inheritdoc}
     */
    public function getMergeFolder(): string
    {
        return $this->settings
            ->mergeFolder();
    }

    /**
     * {@inheritdoc}
     */
    public function getChunksOptions(): array
    {
        return array_merge([
            'disk' => $this->getChunksDisk(),
        ], $this->settings->additionalChunksOptions());
    }

    /**
     * {@inheritdoc}
     */
    public function getMergeOptions(): array
    {
        return array_merge([
            'disk' => $this->getMergeDisk(),
        ], $this->settings->additionalMergeOptions());
    }

    /**
     * {@inheritdoc}
     */
    public function chunks(string $folder): Collection
    {
        return collect($this->chunksFilesystem()->files($folder))
            ->map(function ($path) use ($folder) {
                $filename = str_replace($folder.DIRECTORY_SEPARATOR, '', $path);
                $exploded_name = explode('_', $filename);
                $index = array_shift($exploded_name);

                return [
                    'index' => intval($index),
                    'path'  => $path,
                ];
            })->sortBy(function ($item) {
                return $item['index'];
            });
    }

    /**
     * {@inheritdoc}
     */
    public function checkChunkIntegrity(string $folder, int $index): bool
    {
        $path = $this->fullPath($folder);
        $default = $this->settings->defaultIndex();

        if ($this->chunksFilesystem()->exists($path)) {
            if (ChunkySettings::INDEX_ZERO != $default) {
                $index -= $default;
            }

            return count($this->chunksFilesystem()->files($path)) == $index;
        } elseif ($index == $default) {
            if (!$this->chunksFilesystem()->makeDirectory($path)) {
                throw new ChunksIntegrityException("Cannot create chunks folder $path");
            }

            return true;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addChunk(UploadedFile $file, int $index, string $folder): Chunk
    {
        // Check integrity
        if (!$this->checkChunkIntegrity($folder, $index)) {
            throw new ChunksIntegrityException("Uploaded chunk with index {$index} violates the integrity");
        }

        // Store chunk
        return Chunk::storeFrom(
            $file,
            $this->fullPath($folder),
            $index,
            $this->getChunksOptions()
        );
    }

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
    public function handle(AddChunkRequest $request, $folder = null): Chunk
    {
        $folder = $this->checkFolder($request, $folder);
        $chunk = $this->addChunk(
            $request->fileInput(),
            $request->indexInput(),
            $folder
        );

        if ($this->isLastIndex($request) && $this->settings->autoMerge()) {
            $chunk->setLast(true);

            $this->dispatchMerge($request, $this->fullPath($folder));
        }

        return $chunk;
    }
}
