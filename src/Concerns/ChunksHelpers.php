<?php

namespace Jobtech\LaravelChunky\Concerns;

use Illuminate\Support\Str;

trait ChunksHelpers
{
    protected $progress_bar;

    /**
     * Check if the given chunks folder exists.
     *
     * @param string $folder
     *
     * @return bool
     */
    public function chunksFolderExists($folder = ''): bool
    {
        if (!Str::startsWith($folder, $this->getChunksFolder())) {
            $folder = $this->getChunksFolder().$folder;
        }

        return $this->chunksFilesystem()
            ->exists($folder);
    }

    /**
     * Delete all chunks folders and their content.
     *
     * @return bool
     */
    public function deleteAllChunks(): bool
    {
        $folders = $this->chunksFilesystem()
            ->directories(
                $this->getChunksFolder()
            );

        if (app()->runningInConsole() && !app()->runningUnitTests()) {
            $this->progress_bar = $this->output->createProgressBar(
                count($folders)
            );
        }

        foreach ($folders as $folder) {
            if (!$this->deleteChunks($folder)) {
                return false;
            }

            if (app()->runningInConsole() && !app()->runningUnitTests()) {
                $this->progress_bar->advance();
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
        if (!$this->chunksFolderExists($folder)) {
            return false;
        }

        $files = $this->chunksFilesystem()
            ->allFiles($folder);

        foreach ($files as $file) {
            $deleted = $this->chunksFilesystem()
                ->delete($file);

            if (!$deleted) {
                return false;
            }
        }

        return $this->chunksFilesystem()
            ->deleteDirectory($folder);
    }
}
