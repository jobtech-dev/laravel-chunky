<?php

namespace Jobtech\LaravelChunky\Strategies;

use Illuminate\Container\Container;
use Jobtech\LaravelChunky\ChunkySettings;
use Jobtech\LaravelChunky\Exceptions\ChunkyException;
use Jobtech\LaravelChunky\Exceptions\StrategyException;
use Jobtech\LaravelChunky\Strategies\Contracts\StrategyFactory as StrategyFactoryContract;

class StrategyFactory implements StrategyFactoryContract
{
    private static $instance;
    /**
     * @var \Jobtech\LaravelChunky\ChunkySettings
     */
    private ChunkySettings $settings;

    public function __construct(ChunkySettings $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Returns a new instance for the given strategy class.
     *
     * @param string                                         $strategy
     *
     * @return mixed
     */
    private function buildInstance(string $strategy)
    {
        if (! method_exists($strategy, 'instance')) {
            throw new StrategyException('Cannot instantiate strategy instance');
        }

        return $strategy::instance();
    }

    /**
     * {@inheritdoc}
     */
    public function strategyInstance()
    {
        $default = $this->settings->defaultMergeStrategy();

        if ($default === null) {
            throw new StrategyException('Undefined default strategy');
        }

        return $this->buildInstance($default);
    }

    /**
     * {@inheritdoc}
     */
    public function buildFrom($chunksManager = null, $mergeManager = null)
    {
        $strategy = $this->strategyInstance();

        if ($strategy === null) {
            throw new ChunkyException('Undefined strategy for mime type or no default strategy');
        }

        $strategy->chunksManager($chunksManager);
        $strategy->mergeManager($mergeManager);

        return $strategy;
    }

    public static function getInstance(): StrategyFactory
    {
        if (static::$instance === null) {
            static::$instance = Container::getInstance()->make(StrategyFactoryContract::class);
        }

        return static::$instance;
    }
}
