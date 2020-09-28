<?php

namespace Jobtech\LaravelChunky;

use Illuminate\Container\Container;
use Jobtech\LaravelChunky\Contracts\MergeManager as MergeManagerContract;
use Jobtech\LaravelChunky\Strategies\Contracts\MergeStrategy;
use Jobtech\LaravelChunky\Support\MergeFilesystem;

class MergeManager implements MergeManagerContract
{
    /** @var \Jobtech\LaravelChunky\ChunkySettings */
    private ChunkySettings $settings;

    /** @var \Jobtech\LaravelChunky\Support\MergeFilesystem */
    private $mergeFilesystem;

    public function __construct(ChunkySettings $settings)
    {
        $this->settings = $settings;
        $this->mergeFilesystem = MergeFilesystem::instance([
            'disk' => $settings->mergeDisk(),
            'folder' => $settings->mergeFolder(),
        ]);
    }

    private function defaultMergeStrategy()
    {
        return $this->settings->defaultMergeStrategy();
    }

    public function setStrategy($strategy = null): MergeManager
    {
        if ($strategy instanceof MergeStrategy) {
            $this->strategy = $strategy;
        } elseif ($strategy === null) {
            $this->defaultMergeStrategy();
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function mergeFilesystem(): MergeFilesystem
    {
        return $this->mergeFilesystem;
    }

    /**
     * {@inheritdoc}
     */
    public function getMergeDisk(): ?string
    {
        return $this->settings
            ->mergeDisk();
    }

    /**
     * {@inheritdoc}
     */
    public function getMergeFolder(): string
    {
        return $this->settings
            ->mergeFolder();
    }

    /**
     * {@inheritdoc}
     */
    public function getMergeOptions(): array
    {
        return array_merge([
            'disk' => $this->getMergeDisk(),
        ], $this->settings->additionalMergeOptions());
    }

    /**
     * {@inheritdoc}
     */
    public function store(string $destination, $origin, $options = [])
    {
        return $this->mergeFilesystem->store($destination, $origin, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function destinationPath(string $path): string
    {
        return $this->mergeFilesystem()->destinationPath($path);
    }

    public static function getInstance(): MergeManager
    {
        return Container::getInstance()->make(MergeManagerContract::class);
    }
}
