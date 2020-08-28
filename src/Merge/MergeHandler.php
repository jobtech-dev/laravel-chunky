<?php

namespace Jobtech\LaravelChunky\Merge;

use Illuminate\Container\Container;
use Illuminate\Support\Traits\ForwardsCalls;
use Jobtech\LaravelChunky\Contracts\ChunksManager;
use Jobtech\LaravelChunky\Merge\Strategies\Contracts\MergeStrategy;
use Jobtech\LaravelChunky\Merge\Strategies\Contracts\StrategyFactory;

/**
 * @mixin \Jobtech\LaravelChunky\Merge\Strategies\Contracts\MergeStrategy
 */
class MergeHandler
{
    use ForwardsCalls;

    /**
     * @var \Jobtech\LaravelChunky\Contracts\ChunksManager
     */
    protected $manager;

    /**
     * @var \Jobtech\LaravelChunky\Merge\Strategies\Contracts\MergeStrategy
     */
    private $strategy;

    public function __construct(ChunksManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Set or retrieve the merge strategy of the handler.
     *
     * @param MergeStrategy|null $strategy
     *
     * @return \Jobtech\LaravelChunky\Merge\Strategies\Contracts\MergeStrategy|null
     */
    public function strategy($strategy = null): ?MergeStrategy
    {
        if ($strategy instanceof MergeStrategy) {
            $this->strategy = $strategy;
        }

        return $this->strategy;
    }

    public function __call($method, $parameters)
    {
        if (!method_exists($this, $method)) {
            return $this->forwardCallTo($this->strategy, $method, $parameters);
        }

        return $this->{$method}(...$parameters);
    }

    /**
     * Build a merge strategy for the given mime_type.
     *
     * @param \Jobtech\LaravelChunky\Contracts\ChunksManager $manager
     * @param string                                         $mime_type
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     *
     * @return \Jobtech\LaravelChunky\Merge\Strategies\Contracts\MergeStrategy
     */
    public static function strategyBy(ChunksManager $manager, string $mime_type): MergeStrategy
    {
        return Container::getInstance()
            ->make(StrategyFactory::class)
            ->buildFrom($mime_type, $manager);
    }

    /**
     * Create a new merge handler instance and set the strategy from the given mime type.
     *
     * @param \Jobtech\LaravelChunky\Contracts\ChunksManager $manager
     * @param string                                         $chunks_folder
     * @param string                                         $destination
     * @param string                                         $mime_type
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     *
     * @return \Jobtech\LaravelChunky\Merge\MergeHandler
     */
    public static function create(ChunksManager $manager, string $chunks_folder, string $destination, string $mime_type): MergeHandler
    {
        $strategy = static::strategyBy($manager, $mime_type);
        $strategy->chunksFolder($chunks_folder);
        $strategy->destination($destination);

        $handler = new static($manager);
        $handler->strategy($strategy);

        return $handler;
    }
}
