<?php

namespace Jobtech\LaravelChunky\Tests\Unit;

use Illuminate\Contracts\Config\Repository;
use Jobtech\LaravelChunky\ChunkySettings;
use Jobtech\LaravelChunky\Tests\TestCase;

class ChunkySettingsTest extends TestCase
{
    public function indexProvider()
    {
        return [
            [null, ChunkySettings::INDEX_ZERO],
            [ChunkySettings::INDEX_ZERO, ChunkySettings::INDEX_ZERO],
            [ChunkySettings::INDEX_ONE, ChunkySettings::INDEX_ONE],
            [0, ChunkySettings::INDEX_ZERO],
            [1, ChunkySettings::INDEX_ONE],
            [10, 10],
        ];
    }

    /** @test */
    public function settings_have_config()
    {
        $repository = $this->mock(Repository::class, function ($mock) {
            $mock->shouldReceive('get')
                ->once()
                ->with('chunky')
                ->andReturn([
                    'foo' => 'bar',
                ]);
        });

        $settings = new ChunkySettings($repository);

        $this->assertEquals(['foo' => 'bar'], $settings->config());
    }

    /** @test */
    public function settings_retrieve_default_disk()
    {
        $repository = $this->mock(Repository::class, function ($mock) {
            $mock->shouldReceive('get')
                ->with('chunky')
                ->once()
                ->andReturn([]);
        });

        $settings = new ChunkySettings($repository);

        $this->assertNull($settings->chunksDisk());
        $this->assertNull($settings->mergeDisk());
    }

    /** @test */
    public function settings_retrieve_default_folder()
    {
        $repository = $this->mock(Repository::class, function ($mock) {
            $mock->shouldReceive('get')
                ->with('chunky')
                ->once()
                ->andReturn([]);
        });

        $settings = new ChunkySettings($repository);

        $this->assertEmpty($settings->chunksFolder());
        $this->assertEmpty($settings->mergeFolder());
    }

    /** @test */
    public function settings_retrieve_default_chunks_disk()
    {
        $repository = $this->mock(Repository::class, function ($mock) {
            $mock->shouldReceive('get')
                ->with('chunky')
                ->once()
                ->andReturn([
                    'disks' => [
                        'chunks' => [
                            'disk' => 'foo',
                        ],
                    ],
                ]);
        });

        $settings = new ChunkySettings($repository);

        $this->assertEquals('foo', $settings->chunksDisk());
    }

    /** @test */
    public function settings_retrieve_chunks_folder()
    {
        $repository = $this->mock(Repository::class, function ($mock) {
            $mock->shouldReceive('get')
                ->with('chunky')
                ->once()
                ->andReturn([
                    'disks' => [
                        'chunks' => [
                            'folder' => 'foo',
                        ],
                    ],
                ]);
        });

        $settings = new ChunkySettings($repository);

        $this->assertEquals('foo/', $settings->chunksFolder());
    }

    /** @test */
    public function settings_retrieve_default_merge_disk()
    {
        $repository = $this->mock(Repository::class, function ($mock) {
            $mock->shouldReceive('get')
                ->with('chunky')
                ->once()
                ->andReturn([
                    'disks' => [
                        'merge' => [
                            'disk' => 'foo',
                        ],
                    ],
                ]);
        });

        $settings = new ChunkySettings($repository);

        $this->assertEquals('foo', $settings->mergeDisk());
    }

    /** @test */
    public function settings_retrieve_merge_folder()
    {
        $repository = $this->mock(Repository::class, function ($mock) {
            $mock->shouldReceive('get')
                ->with('chunky')
                ->once()
                ->andReturn([
                    'disks' => [
                        'merge' => [
                            'folder' => 'foo',
                        ],
                    ],
                ]);
        });

        $settings = new ChunkySettings($repository);

        $this->assertEquals('foo/', $settings->mergeFolder());
    }

    /**
     * @test
     * @dataProvider indexProvider
     *
     * @param $index
     * @param $expected
     */
    public function settings_retrieve_default_index($index, $expected)
    {
        $repository = $this->mock(Repository::class, function ($mock) use ($index) {
            $mock->shouldReceive('get')
                ->with('chunky')
                ->once()
                ->andReturn([
                    'index' => $index,
                ]);
        });

        $settings = new ChunkySettings($repository);

        $this->assertEquals($expected, $settings->defaultIndex());
    }

    /** @test */
    public function settings_retrieve_default_additional_chunks_options()
    {
        $repository = $this->mock(Repository::class, function ($mock) {
            $mock->shouldReceive('get')
                ->with('chunky')
                ->once()
                ->andReturn([]);
        });

        $settings = new ChunkySettings($repository);

        $this->assertEquals([], $settings->additionalChunksOptions());
    }

    /** @test */
    public function settings_retrieve_additional_chunks_options()
    {
        $repository = $this->mock(Repository::class, function ($mock) {
            $mock->shouldReceive('get')
                ->with('chunky')
                ->once()
                ->andReturn([
                    'options' => [
                        'chunks' => [
                            'foo' => 'bar',
                        ],
                    ],
                ]);
        });

        $settings = new ChunkySettings($repository);

        $this->assertEquals(['foo' => 'bar'], $settings->additionalChunksOptions());
    }

    /** @test */
    public function settings_retrieve_default_additional_merge_options()
    {
        $repository = $this->mock(Repository::class, function ($mock) {
            $mock->shouldReceive('get')
                ->with('chunky')
                ->once()
                ->andReturn([]);
        });

        $settings = new ChunkySettings($repository);

        $this->assertEquals([], $settings->additionalMergeOptions());
    }

    /** @test */
    public function settings_retrieve_additional_merge_options()
    {
        $repository = $this->mock(Repository::class, function ($mock) {
            $mock->shouldReceive('get')
                ->with('chunky')
                ->once()
                ->andReturn([
                    'options' => [
                        'merge' => [
                            'foo' => 'bar',
                        ],
                    ],
                ]);
        });

        $settings = new ChunkySettings($repository);

        $this->assertEquals(['foo' => 'bar'], $settings->additionalMergeOptions());
    }

    /** @test */
    public function settings_retrieve_default_auto_merge_option()
    {
        $repository = $this->mock(Repository::class, function ($mock) {
            $mock->shouldReceive('get')
                ->with('chunky')
                ->once()
                ->andReturn([]);
        });

        $settings = new ChunkySettings($repository);

        $this->assertFalse($settings->autoMerge());
    }

    /** @test */
    public function settings_retrieve_auto_merge_option()
    {
        $repository = $this->mock(Repository::class, function ($mock) {
            $mock->shouldReceive('get')
                ->with('chunky')
                ->once()
                ->andReturn([
                    'auto_merge' => true,
                ]);
        });

        $settings = new ChunkySettings($repository);

        $this->assertTrue($settings->autoMerge());
    }
}
