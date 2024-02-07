<?php

namespace Jobtech\LaravelChunky\Concerns;

use Illuminate\Support\Str;
use Jobtech\LaravelChunky\Http\Requests\AddChunkRequest;

trait ChunkyRequestHelpers
{
    /**
     * Retrieve last chunk index from chunk request by calculating the ceil value of
     * the total size of the file divided by the size of the single chunk.
     *
     * @param AddChunkRequest $request
     *
     * @return int
     */
    public function lastIndexFrom(AddChunkRequest $request): int
    {
        $total_size = $request->totalSizeInput();
        $chunk_size = $request->chunkSizeInput();

        if ($total_size < $chunk_size) {
            // In this case usually it means that there's only a chunk
            return 1;
        }

        return ceil($total_size / $chunk_size);
    }

    /**
     * Check if current index refers to the last chunk.
     *
     * @param AddChunkRequest $request
     *
     * @return bool
     */
    public function isLastIndex(AddChunkRequest $request): bool
    {
        $last_index = $this->lastIndexFrom($request)
            + ($this->settings->defaultIndex() - 1);

        return $request->indexInput() == $last_index;
    }

    /**
     * Check if folder is a valid string, otherwise guess the folder from the
     * request file input.
     *
     * @param AddChunkRequest $request
     * @param string|null     $folder
     *
     * @return string
     */
    protected function checkFolder(AddChunkRequest $request, ?string $folder)
    {
        $file = $request->fileInput();

        if ($folder !== null) {
            return Str::slug($folder);
        }

        return $this->chunkFolderNameFor(
            str_replace(
                $file->guessExtension(),
                '',
                $file->getClientOriginalName()
            )
        );
    }
}
