<?php

namespace Jobtech\LaravelChunky\Concerns;

use Illuminate\Support\Str;
use Jobtech\LaravelChunky\Events\ChunkDeleted;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Style\OutputStyle;

trait ChunksHelpers
{
    /**
     * This method checks if the application is running in console and, if output style is not null, it creates a new
     * progress bar instance, otherwise returns null.
     *
     * @param \Symfony\Component\Console\Style\OutputStyle|null $output
     * @param int $count
     *
     * @return \Symfony\Component\Console\Helper\ProgressBar|null
     */
    public function hasProgressBar(?OutputStyle $output, int $count) : ?ProgressBar{
        if($output !== null && app()->runningInConsole()) {
            return $output->createProgressBar($count);
        }

        return null;
    }

    /**
     * Check if the given chunks folder exists.
     *
     * @param string $folder
     *
     * @return bool
     */
    public function chunksFolderExists($folder = ''): bool
    {
        if (! Str::startsWith($folder, $this->getChunksFolder())) {
            $folder = $this->getChunksFolder().$folder;
        }

        return $this->chunksFilesystem()
            ->exists($folder);
    }

    /**
     * Delete all chunks folders and their content.
     *
     * @param null $output
     * @return bool
     */
    public function deleteAllChunks($output = null): bool
    {
        $folders = $this->chunksFilesystem()
            ->directories(
                $this->getChunksFolder()
            );

        $progress_bar = $this->hasProgressBar($output, count($folders));

        foreach ($folders as $folder) {
            if (! $this->deleteChunks($folder)) {
                return false;
            }

            if ($progress_bar !== null) {
                $progress_bar->advance();
            }
        }

        return true;
    }

    /**
     * Delete all chunks and, once empty, delete the folder.
     *
     * @param string $folder
     *
     * @return bool
     */
    public function deleteChunks(string $folder): bool
    {
        if (! $this->chunksFolderExists($folder)) {
            return false;
        }

        $files = $this->chunks($folder);

        foreach ($files as $chunk) {
            $deleted = $this->chunksFilesystem()
                ->delete($chunk->getPath());

            if (! $deleted) {
                return false;
            }

            event(new ChunkDeleted($chunk));
        }

        return $this->chunksFilesystem()
            ->deleteDirectory($folder);
    }
}
