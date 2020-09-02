<?php

namespace Jobtech\LaravelChunky\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Jobtech\LaravelChunky\Events\ChunksMerged;
use Jobtech\LaravelChunky\Exceptions\ChunksIntegrityException;
use Jobtech\LaravelChunky\Handlers\MergeHandler;

class MergeChunks implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @var string
     */
    private $folder;

    /**
     * @var string
     */
    private $destination;

    /**
     * @var string
     */
    private $mime_type;

    /**
     * @var int
     */
    private $chunk_size;

    /**
     * @var int
     */
    private $total_size;

    /**
     * Create a new job instance.
     *
     * @param string $folder
     * @param string $destination
     * @param string $mime_type
     * @param int $chunk_size
     * @param int $total_size
     */
    public function __construct(string $folder, string $destination, string $mime_type, int $chunk_size, int $total_size)
    {
        $this->folder = $folder;
        $this->destination = $destination;
        $this->mime_type = $mime_type;
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
        $handler = MergeHandler::create(
            $this->folder,
            $this->destination,
            $this->mime_type
        );

        if (! $handler->checkIntegrity($this->chunk_size, $this->total_size)) {
            throw new ChunksIntegrityException('Chunks total file size doesnt match with original file size');
        }

        $strategy = $handler->merge();

        event(new ChunksMerged(
            $strategy, $strategy->destination()
        ));
    }
}
