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

            if ($size < $chunk_size && ! $chunk->isLast()) {
                return false;
            }

            $total += $size;
        }

        return $total >= $total_size;
    }
}
