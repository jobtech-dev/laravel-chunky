<?php

namespace Jobtech\LaravelChunky\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Jobtech\LaravelChunky\ChunksManager;

class MergeChunks implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @var string
     */
    private string $folder;

    /**
     * @var string
     */
    private string $destination;

    /**
     * @var int
     */
    private int $chunk_size;

    /**
     * @var int
     */
    private $total_size;

    /**
     * Create a new job instance.
     *
     * @param string $folder
     * @param string $destination
     * @param int $chunk_size
     * @param int $total_size
     */
    public function __construct(string $folder, string $destination, int $chunk_size, int $total_size)
    {
        $this->folder = $folder;
        $this->destination = $destination;
        $this->chunk_size = $chunk_size;
        $this->total_size = $total_size;
    }

    /**
     * Execute the job.
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     *
     * @return void
     */
    public function handle()
    {
        $manager = ChunksManager::getInstance();

        $manager->handleMerge(
            $this->folder,
            $this->destination,
            $this->chunk_size,
            $this->total_size
        );
    }
}
