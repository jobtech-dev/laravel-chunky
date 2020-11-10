<?php

namespace Jobtech\LaravelChunky\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Jobtech\LaravelChunky\Exceptions\ChunksIntegrityException;
use Jobtech\LaravelChunky\Facades\Chunky;

class MergeChunks implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private string $chunks_folder;

    private string $merge_path;

    private int $chunk_size;

    private int $total_size;

    /**
     * Create a new job instance.
     *
     * @param string $chunks_folder
     * @param string $merge_path
     * @param int $chunk_size
     * @param int $total_size
     */
    public function __construct(string $chunks_folder, string $merge_path, int $chunk_size, int $total_size)
    {
        $this->chunks_folder = $chunks_folder;
        $this->merge_path = $merge_path;
        $this->chunk_size = $chunk_size;
        $this->total_size = $total_size;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (! Chunky::checkChunksIntegrity($this->chunks_folder, $this->chunk_size, $this->total_size)) {
            throw new ChunksIntegrityException('Chunks total file size doesnt match with original file size');
        }

        Chunky::merge($this->chunks_folder, $this->merge_path);
    }
}
