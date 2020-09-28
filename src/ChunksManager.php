<?php

namespace Jobtech\LaravelChunky;

use Illuminate\Container\Container;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Jobtech\LaravelChunky\Concerns\ManagerHelpers;
use Jobtech\LaravelChunky\Concerns\ChunkyRequestHelpers;
use Jobtech\LaravelChunky\Contracts\ChunksManager as ChunksManagerContract;
use Jobtech\LaravelChunky\Events\ChunksMerged;
use Jobtech\LaravelChunky\Exceptions\ChunksIntegrityException;
use Jobtech\LaravelChunky\Http\Requests\AddChunkRequest;
use Jobtech\LaravelChunky\Jobs\MergeChunks;
use Jobtech\LaravelChunky\Strategies\Contracts\MergeStrategy;
use Jobtech\LaravelChunky\Strategies\StrategyFactory;
use Jobtech\LaravelChunky\Support\ChunksFilesystem;

class ChunksManager implements ChunksManagerContract
{
    use ManagerHelpers;
    use ChunkyRequestHelpers;

    /** @var \Jobtech\LaravelChunky\ChunksManager|null */
    public static ?ChunksManager $instance = null;

    /** @var \Jobtech\LaravelChunky\ChunkySettings */
    private ChunkySettings $settings;

    /** @var \Jobtech\LaravelChunky\Support\ChunksFilesystem */
    private $chunksFilesystem;

    public function __construct(ChunkySettings $settings)
    {
        $this->settings = $settings;

        $this->chunksFilesystem = ChunksFilesystem::instance([
            'disk' => $settings->chunksDisk(),
            'folder' => $settings->chunksFolder(),
        ]);
    }

    /**
     * Chunks destination folder from file name slug.
     *
     * @param string $file
     *
     * @return string
     */
    private function chunkFolderNameFor(string $file)
    {
        return Str::slug($file);
    }

    /**
     * Dispatch a merge event.
     *
     * @param \Jobtech\LaravelChunky\Http\Requests\AddChunkRequest $request
     * @param string                                               $folder
     */
    private function dispatchMerge(AddChunkRequest $request, string $folder)
    {
        // TODO: Refactor

        if (empty($connection = $this->settings->connection())) {
            $this->handleMerge(
                $this->chunksFilesystem->fullPath($folder),
                $request->fileInput(),
                $request->chunkSizeInput(),
                $request->totalSizeInput()
            );
        } else {
            MergeChunks::dispatch(
                $this->chunksFilesystem->fullPath($folder),
                $request->fileInput(),
                $request->chunkSizeInput(),
                $request->totalSizeInput()
            )->onConnection($connection)
                ->onQueue($this->settings->queue());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function chunksFilesystem(): ChunksFilesystem
    {
        return $this->chunksFilesystem;
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
    public function getChunksFolder(): string
    {
        return $this->settings
            ->chunksFolder();
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
    public function validFolder(string $folder): bool
    {
        return $this->chunksFilesystem()->exists(
            $this->chunksFilesystem->fullPath($folder)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function temporaryFiles(string $folder): Collection {
        $chunks = $this->chunks($folder);
        if(! $this->chunksFilesystem->isLocal()) {
            return $this->chunksFilesystem->createTemporaryFiles($chunks);
        }

        return $chunks->map(function (Chunk $chunk) {
            return $chunk->getPath();
        });
    }

    public function origin($path) {
        if(! $this->chunksFilesystem->isLocal()) {
            return fopen($path, 'r');
        }

        return $this->chunk($path);
    }

    /**
     * {@inheritdoc}
     */
    public function chunks($folder = null): Collection
    {
        return $this->chunksFilesystem()->listChunks($folder);
    }

    /**
     * {@inheritdoc}
     */
    public function chunk($chunk)
    {
        if($chunk instanceof Chunk) {
            $chunk = $chunk->getPath();
        }

        return $this->chunksFilesystem()
            ->readChunk($chunk);
    }

    /**
     * {@inheritdoc}
     */
    public function addChunk(UploadedFile $file, int $index, string $folder): Chunk
    {
        // Check integrity
        if (! $this->checkChunkIntegrity($folder, $index)) {
            throw new ChunksIntegrityException("Uploaded chunk with index {$index} violates the integrity");
        }

        // Store chunk
        return $this->chunksFilesystem->store(
            Chunk::create(
                $file,
                $index,
                $this->getChunksOptions()
            ),
            $this->chunksFilesystem->fullPath($folder),
            $this->getChunksOptions()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function deleteChunkFolder(string $folder): bool
    {
        return $this->chunksFilesystem->delete($folder);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteAllChunks($output = null): bool
    {
        $folders = $this->chunksFilesystem()->folders();

        $progress_bar = $this->hasProgressBar($output, count($folders));

        foreach ($folders as $folder) {
            if (! $this->deleteChunkFolder($folder)) {
                return false;
            }

            if ($progress_bar !== null) {
                $progress_bar->advance();
            }
        }

        if ($progress_bar !== null) {
            $progress_bar->finish();
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function handle(AddChunkRequest $request, $folder = null): Chunk
    {
        // Store chunk
        $folder = $this->checkFolder($request, $folder);
        $chunk = $this->addChunk(
            $request->fileInput(),
            $request->indexInput(),
            $folder
        );

        $chunk->setLast(
            $this->isLastIndex($request)
        );

        // Check merge
        if ($chunk->isLast() && $this->settings->autoMerge()) {
            $this->dispatchMerge($request, $folder);
        }

        return $chunk;
    }

    /**
     * @param string $folder
     * @param string $destination
     * @param int $chunk_size
     * @param int $total_size
     */
    public function handleMerge(string $folder, string $destination, int $chunk_size, int $total_size) {
        if (! $this->checkFilesIntegrity($folder, $chunk_size, $total_size)) {
            throw new ChunksIntegrityException('Chunks total file size doesnt match with original file size');
        }

        $factory = StrategyFactory::getInstance();
        /** @var MergeStrategy $strategy */
        $strategy = $factory->buildFrom($this, MergeManager::getInstance());
        $strategy->chunksFolder($folder);
        $strategy->destination($destination);

        $strategy->merge();

        event(new ChunksMerged(
            $strategy, $strategy->destination()
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function checkChunkIntegrity(string $folder, int $index): bool
    {
        $path = $this->chunksFilesystem()->fullPath($folder);
        $default = $this->settings->defaultIndex();

        if (! $this->chunksFilesystem()->exists($path) && $index != $default) {
            return false;
        } elseif ($this->chunksFilesystem()->exists($path)) {
            if (ChunkySettings::INDEX_ZERO != $default) {
                $index -= $default;
            }

            return $this->chunksFilesystem()->chunksCount($path) == $index;
        } elseif ($index == $default) {
            if (! $this->chunksFilesystem()->makeDirectory($path)) {
                throw new ChunksIntegrityException("Cannot create chunks folder $path");
            }
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function checkFilesIntegrity(string $folder, int $chunk_size, int $total_size): bool
    {
        $total = 0;
        $chunks = $this->chunks($folder);

        foreach ($chunks as $chunk) {
            $size = $this->chunksFilesystem->chunkSize($chunk->getPath());

            if ($size < $chunk_size && ! $chunk->isLast()) {
                return false;
            }

            $total += $size;
        }

        return $total >= $total_size;
    }

    public static function getInstance(): ChunksManager
    {
        if(static::$instance === null) {
            static::$instance = Container::getInstance()->make(ChunksManagerContract::class);
        }

        return static::$instance;
    }
}
