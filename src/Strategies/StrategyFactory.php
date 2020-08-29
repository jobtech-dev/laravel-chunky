<?php

namespace Jobtech\LaravelChunky\Strategies;

use Illuminate\Support\Arr;
use Jobtech\LaravelChunky\Contracts\ChunksManager;
use Jobtech\LaravelChunky\Exceptions\StrategyException;
use Jobtech\LaravelChunky\Strategies\Contracts\MergeStrategy;
use Jobtech\LaravelChunky\Strategies\Contracts\StrategyFactory as StrategyFactoryContract;

class StrategyFactory implements StrategyFactoryContract
{
    /**
     * @var array
     */
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Returns a new instance for the given strategy class.
     *
     * @param string                                         $strategy
     * @param \Jobtech\LaravelChunky\Contracts\ChunksManager|null $manager
     *
     * @return mixed
     */
    private function buildInstance(string $strategy, $manager = null)
    {
        if (!method_exists($strategy, 'newInstance')) {
            throw new StrategyException('Cannot instantiate strategy instance');
        }

        return $strategy::newInstance($manager);
    }

    /**
     * {@inheritdoc}
     */
    public function default($manager = null): MergeStrategy
    {
        if (!Arr::has($this->config, 'default')) {
            throw new StrategyException('Undefined default strategy');
        }

        return $this->buildInstance($this->config['default'], $manager);
    }

    /**
     * {@inheritdoc}
     */
    public function mimeTypes(): array
    {
        return array_keys(
            Arr::get($this->config, 'mime_types', [])
        );
    }

    /**
     * {@inheritdoc}
     */
    public function strategies(): array
    {
        return Arr::get($this->config, 'mime_types', []);
    }

    /**
     * {@inheritdoc}
     */
    public function buildFrom(string $mime_type, $manager = null): MergeStrategy
    {
        $mime_type_group = explode('/', $mime_type)[0].'/*';

        if (in_array($mime_type, $this->mimeTypes())) {
            return $this->buildInstance(
                $this->strategies()[$mime_type],
                $manager
            );
        } elseif (in_array($mime_type_group, $this->mimeTypes())) {
            return $this->buildInstance(
                $this->strategies()[$mime_type_group],
                $manager
            );
        }

        return $this->default($manager);
    }
}
