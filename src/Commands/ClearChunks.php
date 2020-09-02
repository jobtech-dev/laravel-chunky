<?php

namespace Jobtech\LaravelChunky\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Jobtech\LaravelChunky\Contracts\ChunksManager;

class ClearChunks extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chunky:clear
    {folder? : The chunk folder}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete all chunks and relative folder.';
    /**
     * @var \Jobtech\LaravelChunky\Contracts\ChunksManager
     */
    private $manager;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @param \Jobtech\LaravelChunky\Contracts\ChunksManager $manager
     *
     * @return mixed
     */
    public function handle(ChunksManager $manager)
    {
        $this->manager = $manager;

        if (! $this->confirmToProceed()) {
            return;
        }

        $root = $this->manager->getChunksFolder();
        $folder = $this->argument('folder');

        if (! empty($folder)) {
            $root .= $folder;

            if (! $this->manager->deleteChunks($root)) {
                $this->error("An error occurred while deleting folder {$root}");

                return;
            }

            $this->info("folder {$root} cleared!");
        } else {
            if (! $this->manager->deleteAllChunks($this->output)) {
                $this->error("An error occurred while deleting folder {$folder}");

                return;
            }

            $this->info('Chunks folder cleared!');
        }
    }
}
