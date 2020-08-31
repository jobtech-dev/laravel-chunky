<?php

namespace Jobtech\LaravelChunky\Handlers;

use Illuminate\Container\Container;
use Illuminate\Support\Traits\ForwardsCalls;
use Jobtech\LaravelChunky\Strategies\Contracts\MergeStrategy;
use Jobtech\LaravelChunky\Strategies\Contracts\StrategyFactory;

/**
 * @mixin \Jobtech\LaravelChunky\Strategies\Contracts\MergeStrategy
 */
class MergeHandler
{
    use ForwardsCalls;

    /**
     * @var \Jobtech\LaravelChunky\Strategies\Contracts\MergeStrategy
     */
    private $strategy;

    /**
     * Set or retrieve the merge strategy of the handler.
     *
     * @param MergeStrategy|null $strategy
     *
     * @return \Jobtech\LaravelChunky\Strategies\Contracts\MergeStrategy|null
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
        if (! method_exists($this, $method)) {
            return $this->forwardCallTo($this->strategy, $method, $parameters);
        }

        return $this->{$method}(...$parameters);
    }

    /**
     * Build a merge strategy for the given mime_type.
     *
     * @param string $mime_type
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     *
     * @return \Jobtech\LaravelChunky\Strategies\Contracts\MergeStrategy
     */
    public static function strategyBy(string $mime_type): MergeStrategy
    {
        return Container::getInstance()
            ->make(StrategyFactory::class)
            ->buildFrom($mime_type);
    }

    /**
     * Create a new merge handler instance and set the strategy from the given mime type.
     *
     * @param string $chunks_folder
     * @param string $destination
     * @param string $mime_type
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     *
     * @return \Jobtech\LaravelChunky\Handlers\MergeHandler
     */
    public static function create(string $chunks_folder, string $destination, string $mime_type): MergeHandler
    {
        $strategy = static::strategyBy($mime_type);
        $strategy->chunksFolder($chunks_folder);
        $strategy->destination($destination);

        $handler = new static();
        $handler->strategy($strategy);

        return $handler;
    }
}
