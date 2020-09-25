<?php

namespace Jobtech\LaravelChunky\Strategies;

use Illuminate\Container\Container;
use Illuminate\Support\Arr;
use Jobtech\LaravelChunky\ChunkySettings;
use Jobtech\LaravelChunky\Contracts\ChunksManager;
use Jobtech\LaravelChunky\Contracts\MergeManager;
use Jobtech\LaravelChunky\Exceptions\ChunkyException;
use Jobtech\LaravelChunky\Exceptions\StrategyException;
use Jobtech\LaravelChunky\Strategies\Contracts\MergeStrategy;
use Jobtech\LaravelChunky\Strategies\Contracts\StrategyFactory as StrategyFactoryContract;

class StrategyFactory implements StrategyFactoryContract
{
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
        if (!method_exists($strategy, 'newInstance')) {
            throw new StrategyException('Cannot instantiate strategy instance');
        }

        return $strategy::newInstance();
    }

    /**
     * Build a strategy instance by mime types configuration.
     *
     * @param $mime_type
     *
     * @return mixed
     */
    private function resolveByMimeType($mime_type)
    {
        if (in_array($mime_type, $this->mimeTypes())) {
            return $this->buildInstance(
                $this->strategies()[$mime_type]
            );
        }

        $mime_type_group = explode('/', $mime_type)[0].'/*';
        if (in_array($mime_type_group, $this->mimeTypes())) {
            return $this->buildInstance(
                $this->strategies()[$mime_type_group]
            );
        }

        return $this->default();
    }

    /**
     * {@inheritdoc}
     */
    public function strategies(): array
    {
        return $this->settings->strategiesMimeTypes();
    }

    /**
     * {@inheritdoc}
     */
    public function mimeTypes(): array
    {
        return array_keys(
            $this->settings->strategiesMimeTypes()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function default()
    {
        $default = $this->settings->defaultMergeStrategy();

        if ($default !== null) {
            throw new StrategyException('Undefined default strategy');
        }

        return $this->buildInstance($default);
    }

    /**
     * {@inheritdoc}
     */
    public function buildFrom(string $mime_type, $chunksManager = null, $mergeManager = null)
    {
        $strategy = $this->resolveByMimeType($mime_type);

        if($strategy === null) {
            throw new ChunkyException("Undefined strategy for mime type or no default strategy");
        }

        $strategy->chunksManager($chunksManager);
        $strategy->mergeManager($mergeManager);

        return $strategy;
    }

    public static function getInstance(): StrategyFactory
    {
        return Container::getInstance()->make(StrategyFactoryContract::class);
    }
}
