<?php

namespace Jobtech\LaravelChunky;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Jobtech\LaravelChunky\Contracts\MergeHandler;
use Jobtech\LaravelChunky\Support\MergeFilesystem;
use Jobtech\LaravelChunky\Support\ChunksFilesystem;
use Jobtech\LaravelChunky\Concerns\ChunkyRequestHelpers;
use Jobtech\LaravelChunky\Http\Requests\AddChunkRequest;
use Jobtech\LaravelChunky\Exceptions\ChunksIntegrityException;
use Jobtech\LaravelChunky\Contracts\ChunkyManager as ChunkyManagerContract;

/**
 * @method int  lastIndexFrom(AddChunkRequest $request)
 * @method bool isLastIndex(AddChunkRequest $request)
 */
class ChunkyManager implements ChunkyManagerContract
{
    use ChunkyRequestHelpers;

    /** @var ChunksFilesystem */
    protected ChunksFilesystem $chunksFilesystem;

    /** @var ChunkySettings */
    private ChunkySettings $settings;

    /** @var MergeFilesystem */
    private MergeFilesystem $mergeFilesystem;

    /** @var MergeHandler */
    private MergeHandler $mergeHandler;

    public function __construct(ChunkySettings $settings)
    {
        $this->settings = $settings;

        $this->setChunksFilesystem($settings->chunksDisk(), $settings->chunksFolder());
        $this->setMergeFilesystem($settings->mergeDisk(), $settings->mergeFolder());
    }

    /**
     * {@inheritdoc}
     */
    public function settings(): ChunkySettings
    {
        return $this->settings;
    }

    /**
     * {@inheritdoc}
     */
    public function setChunksFilesystem(string $disk, string $folder): ChunkyManager
    {
        $this->chunksFilesystem = ChunksFilesystem::instance(
            compact('disk', 'folder')
        );

        return $this;
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
    public function setMergeFilesystem(string $disk, string $folder): ChunkyManager
    {
        $this->mergeFilesystem = MergeFilesystem::instance(
            compact('disk', 'folder')
        );

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function mergeFilesystem(): MergeFilesystem
    {
        return $this->mergeFilesystem;
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    public function mergeHandler(): MergeHandler
    {
        return $this->settings->mergeHandler()->setManager($this);
    }

    /**
     * {@inheritdoc}
     */
    public function chunksDisk(): ?string
    {
        return $this->settings
            ->chunksDisk();
    }

    /**
     * {@inheritdoc}
     */
    public function mergeDisk(): ?string
    {
        return $this->settings
            ->mergeDisk();
    }

    /**
     * {@inheritdoc}
     */
    public function chunksFolder(): string
    {
        return $this->settings
            ->chunksFolder();
    }

    /**
     * {@inheritdoc}
     */
    public function mergeFolder(): string
    {
        return $this->settings
            ->mergeFolder();
    }

    /**
     * {@inheritdoc}
     */
    public function chunksOptions(): array
    {
        return array_merge([
            'disk' => $this->chunksDisk(),
        ], $this->settings->additionalChunksOptions());
    }

    /**
     * {@inheritdoc}
     */
    public function mergeOptions(): array
    {
        return $this->settings->additionalMergeOptions();
    }

    /**
     * {@inheritdoc}
     */
    public function listChunks(?string $folder = null): Collection
    {
        return $this->chunksFilesystem()->listChunks($folder);
    }

    /**
     * {@inheritdoc}
     */
    public function readChunk($chunk)
    {
        if ($chunk instanceof Chunk) {
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
        if (!$this->checkChunks($folder, $index)) {
            throw new ChunksIntegrityException("Uploaded chunk with index {$index} violates the integrity");
        }

        $chunk = Chunk::create($file, $index, $this->chunksOptions());

        // Store chunk
        return $this->chunksFilesystem()
            ->store($chunk, $folder, $this->chunksOptions());
    }

    /**
     * {@inheritdoc}
     */
    public function deleteChunks(string $folder): bool
    {
        return $this->chunksFilesystem()->delete($folder);
    }

    /**
     * @param string $folder
     *
     * @return bool
     */
    public function isValidChunksFolder(string $folder): bool
    {
        return $this->chunksFilesystem()->exists($folder);
    }

    /**
     * {@inheritdoc}
     */
    public function checkChunks(string $folder, int $index): bool
    {
        $default = $this->settings->defaultIndex();

        if (!$this->chunksFilesystem()->exists($folder) && $index != $default) {
            return false;
        }

        if ($this->chunksFilesystem()->exists($folder)) {
            if ($default != ChunkySettings::INDEX_ZERO) {
                $index -= $default;
            }

            return $this->chunksFilesystem()->chunksCount($folder) == $index;
        }

        if ($index == $default) {
            if (!$this->chunksFilesystem()->makeDirectory($folder)) {
                throw new ChunksIntegrityException("Cannot create chunks folder {$folder}");
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function checkChunksIntegrity(string $folder, int $chunk_size, int $total_size): bool
    {
        $total = 0;
        $chunks = $this->listChunks($folder);

        foreach ($chunks as $chunk) {
            $size = $this->chunksFilesystem()->chunkSize($chunk->getPath());

            if ($size < $chunk_size && !$chunk->isLast()) {
                return false;
            }

            $total += $size;
        }

        return $total >= $total_size;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(AddChunkRequest $request, ?string $folder = null): Chunk
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
            $this->mergeHandler()->dispatchMerge($request, $folder);
        }

        return $chunk;
    }

    /**
     * {@inheritdoc}
     */
    public function merge(string $chunks_folder, string $merge_path): string
    {
        return $this->mergeHandler()->merge($chunks_folder, $merge_path);
    }
}
