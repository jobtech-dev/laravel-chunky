<?php

namespace Jobtech\LaravelChunky\Strategies\Concerns;

trait ChecksIntegrity
{
    /**
     * {@inheritdoc}
     */
    public function checkIntegrity(int $chunk_size, int $total_size): bool
    {
        $total = 0;
        $chunks = $this->chunks(
            $this->chunksFolder()
        );

        foreach ($chunks as $chunk) {
            $size = $this->chunksFilesystem()->size($chunk->getPath());

            if ($size < $chunk_size && ! $this->isLastChunk($chunk->getIndex(), $total_size, $chunk_size)) {
                return false;
            }

            $total += $size;
        }

        return $total >= $total_size;
    }

    public function isLastChunk(int $index, int $total_size, int $chunk_size)
    {
        $last_index = ceil($total_size / $chunk_size);

        return $index == $last_index;
    }
}
