<?php

namespace Jobtech\LaravelChunky\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Jobtech\LaravelChunky\Chunk;
use Illuminate\Support\Collection;
use Keven\AppendStream\AppendStream;
use League\Flysystem\StorageAttributes;
use League\Flysystem\FilesystemException;
use Jobtech\LaravelChunky\Events\ChunkAdded;
use Keven\Flysystem\Concatenate\Concatenate;
use Jobtech\LaravelChunky\Events\ChunkDeleted;
use Symfony\Component\HttpFoundation\File\File;
use Jobtech\LaravelChunky\Exceptions\ChunkyException;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class ChunksFilesystem extends Filesystem
{
    /**
     * @param string $folder
     *
     * @return Collection
     */
    public function listChunks(string $folder): Collection
    {
        $folder = $this->path($folder);
        $files = $this->list($folder);

        return collect($files)
            ->map(function ($path) use ($folder, $files) {
                $filename = str_replace($folder.DIRECTORY_SEPARATOR, '', $path);
                $exploded_name = explode('_', $filename);

                $index = array_shift($exploded_name);
                $last = count($files) - 1 == $index;

                return new Chunk((int) $index, $path, $this->disk(), $last);
            })->sortBy(function (Chunk $chunk) {
                return $chunk->getIndex();
            })->values();
    }

    /**
     * @return array
     *
     * @throws FilesystemException
     */
    public function chunkFolders(): array
    {
        return $this->filesystem()->disk($this->disk())->listContents($this->folder(), false)
            ->filter(fn (StorageAttributes $attributes) => $attributes->isDir())
            ->sortByPath()
            ->map(fn (StorageAttributes $attributes) => $attributes->path())
            ->toArray();
    }

    /**
     * @param string $folder
     *
     * @return int
     */
    public function chunksCount(string $folder): int
    {
        return count($this->list($this->path($folder)));
    }

    /**
     * @param string $path
     *
     * @return int
     */
    public function chunkSize(string $path): int
    {
        return $this->filesystem()->disk($this->disk())->size($this->path($path));
    }

    /**
     * @param string $path
     *
     * @return resource|null
     */
    public function readChunk(string $path)
    {
        return $this->filesystem()->disk($this->disk())->readStream($this->path($path));
    }

    /**
     * @param Chunk  $chunk
     * @param string $folder
     * @param array  $options
     *
     * @return Chunk
     */
    public function store(Chunk $chunk, string $folder, $options = []): Chunk
    {
        if (!$chunk->getOriginalPath() instanceof File) {
            throw new ChunkyException('Path must be a file');
        }

        // Build destination
        $suffix = Str::endsWith($folder, DIRECTORY_SEPARATOR) ? '' : DIRECTORY_SEPARATOR;
        $destination = $this->path($folder.$suffix.$chunk->getSlug());

        // Copy file
        $file = fopen($chunk->getPath(), 'r');

        Arr::pull($options, 'disk');
        $this->filesystem()->disk($this->disk())->put($destination, $file, $options);
        fclose($file);

        // Return chunk
        $chunk->setPath($destination);
        event(new ChunkAdded($chunk));

        return $chunk;
    }

    /**
     * Delete all chunks and, once empty, delete the folder.
     *
     * @param Chunk $chunk
     *
     * @return bool
     */
    public function deleteChunk(Chunk $chunk): bool
    {
        if (!$this->filesystem()->disk($this->disk())->exists($chunk->getPath())) {
            return true;
        }

        $deleted = $this->filesystem()->disk($this->disk())->delete($chunk->getPath());

        if ($deleted) {
            event(new ChunkDeleted($chunk));
        }

        return $deleted;
    }

    /**
     * Delete all chunks and, once empty, delete the folder.
     *
     * @param string $folder
     *
     * @return bool
     */
    public function delete(string $folder): bool
    {
        $folder = $this->path($folder);

        if (!$this->filesystem()->disk($this->disk())->exists($folder)) {
            return true;
        }

        foreach ($this->listChunks($folder) as $chunk) {
            $this->deleteChunk($chunk);
        }

        return $this->filesystem()->disk($this->disk())
            ->deleteDirectory($folder);
    }

    /**
     * Concatenate all chunks into final merge.
     *
     * @param string $chunk
     * @param array  $chunks
     *
     * @return bool
     *
     * @throws FileNotFoundException|FilesystemException
     */
    public function concatenate(string $chunk, array $chunks): bool
    {
        foreach ($chunks as $path) {
            if (!$this->filesystem()->disk($this->disk())->has($path)) {
                throw new FileNotFoundException($path);
            }
        }

        $overwrite = in_array($chunk, $chunks, true);
        if ($overwrite) {
            $this->filesystem()->disk($this->disk())->move($chunk, $chunkBackupPath = $chunk.'.backup');
            $key = array_search($chunk, $chunks, true);
            $chunks[$key] = $chunkBackupPath;
        }

        $stream = new AppendStream();
        foreach ($chunks as $fragment) {
            $stream->append($this->filesystem()->disk($this->disk())->readStream($fragment));
        }
        $this->filesystem()->disk($this->disk())->writeStream($chunk, $resource = $stream->getResource());

        if ($overwrite) {
            $this->filesystem()->disk($this->disk())->delete($chunkBackupPath);
        }

        return true;
    }
}
