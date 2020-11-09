<?php

namespace Jobtech\LaravelChunky\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Jobtech\LaravelChunky\Contracts\ChunkyManager;

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
     * @var \Jobtech\LaravelChunky\Contracts\ChunkyManager
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
     * @param \Jobtech\LaravelChunky\Contracts\ChunkyManager $manager
     *
     * @return mixed
     */
    public function handle(ChunkyManager $manager)
    {
        $this->manager = $manager;

        if (! $this->confirmToProceed()) {
            return;
        }

        $root = $this->manager->chunksFolder();
        $folder = $this->argument('folder');

        if (! empty($folder)) {
            $root .= $folder;
            $this->deleteFolder($root);

            return;
        }

        $folders = $this->manager->chunksFilesystem()->chunkFolders();
        $bar = $this->output->createProgressBar(count($folders));

        $bar->start();

        foreach($folders as $folder) {
            $this->deleteFolder($folder);
        }

        $bar->finish();
        $this->info('Chunks folders have been deleted!');
    }

    /**
     * @param string $folder
     */
    private function deleteFolder(string $folder)
    {
        if (! $this->manager->deleteChunks($folder)) {
            $this->error("An error occurred while deleting folder {$folder}");

            return;
        }

        $this->info("folder {$folder} has been deleted!");
    }
}
