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
        $chunks = $this->manager()->chunks(
            $this->chunksFolder()
        );

        foreach ($chunks->all() as $chunk) {
            $size = $this->manager()->chunksFilesystem()->size($chunk['path']);

            if ($size < $chunk_size && !$this->isLastChunk($chunk['index'], $total_size, $chunk_size)) {
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
