<?php

namespace Jobtech\LaravelChunky\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Jobtech\LaravelChunky\Contracts\ChunksManager;
use Jobtech\LaravelChunky\Exceptions\ChunksIntegrityException;
use Jobtech\LaravelChunky\Http\Requests\AddChunkRequest;
use Jobtech\LaravelChunky\Merge\MergeHandler;

class MergeChunks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var \Jobtech\LaravelChunky\Contracts\ChunksManager
     */
    private $manager;

    /**
     * @var \Jobtech\LaravelChunky\Http\Requests\AddChunkRequest
     */
    private $request;

    /**
     * @var string
     */
    private $folder;

    /**
     * @var string
     */
    private $destination;

    /**
     * Create a new job instance.
     *
     * @param \Jobtech\LaravelChunky\Contracts\ChunksManager $manager
     * @param \Jobtech\LaravelChunky\Http\Requests\AddChunkRequest $request
     * @param string $folder
     * @param string $destination
     */
    public function __construct(ChunksManager $manager, AddChunkRequest $request, string $folder, string $destination)
    {
        $this->manager = $manager;
        $this->request = $request;
        $this->folder = $folder;
        $this->destination = $destination;
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
            $this->manager,
            $this->folder,
            $this->destination,
            $this->request->fileInput()->getMimeType()
        );

        if(! $handler->checkIntegrity($this->request->chunkSizeInput(), $this->request->totalSizeInput())) {
            throw new ChunksIntegrityException("Chunks total file size doesnt match with original file size");
        }

        $handler->merge();
    }
}