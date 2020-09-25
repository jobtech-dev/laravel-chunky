<?php

namespace Jobtech\LaravelChunky\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Jobtech\LaravelChunky\ChunksManager;
use Jobtech\LaravelChunky\Contracts\MergeManager;
use Jobtech\LaravelChunky\Events\ChunksMerged;
use Jobtech\LaravelChunky\Exceptions\ChunksIntegrityException;
use Jobtech\LaravelChunky\Strategies\Contracts\MergeStrategy;
use Jobtech\LaravelChunky\Strategies\StrategyFactory;

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
     * @var string
     */
    private string $mime_type;

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
        $manager = ChunksManager::getInstance();
        if (! $manager->checkFilesIntegrity($this->folder, $this->chunk_size, $this->total_size)) {
            throw new ChunksIntegrityException('Chunks total file size doesnt match with original file size');
        }

        $factory = StrategyFactory::getInstance();
        /** @var MergeStrategy $strategy */
        $strategy = $factory->buildFrom($this->mime_type, $manager, MergeManager::getInstance());
        $strategy->chunksFolder($this->folder);
        $strategy->destination($this->destination);

        $strategy->boot()->merge();

        event(new ChunksMerged(
            $strategy, $strategy->destination()
        ));
    }
}
