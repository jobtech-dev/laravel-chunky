<?php

namespace Jobtech\LaravelChunky\Tests\Unit\Http\Requests;

use Illuminate\Config\Repository;
use Illuminate\Http\UploadedFile;
use Jobtech\LaravelChunky\Tests\TestCase;
use Jobtech\LaravelChunky\Exceptions\ChunkyException;
use Jobtech\LaravelChunky\Http\Requests\AddChunkRequest;

/**
 * @internal
 */
class AddChunkRequestTest extends TestCase
{
    /** @test */
    public function requestThrowExceptionOnMissingIndexRules()
    {
        $mock = $this->mock(Repository::class, function ($mock) {
            $mock->shouldReceive('get')
                ->once()
                ->with('chunky.validation.index.key', null)
                ->andReturn(null);
            $mock->shouldReceive('get')
                ->once()
                ->with('chunky.validation.index.rules', [])
                ->andReturn([]);
        });
        $this->app->bind('config', function () use ($mock) {
            return $mock;
        });

        $request = new AddChunkRequest();

        $this->expectException(ChunkyException::class);

        $request->indexRules();
    }

    /** @test */
    public function requestRetrievesDefaultIndexRules()
    {
        $mock = $this->mock(Repository::class, function ($mock) {
            $mock->shouldReceive('get')
                ->once()
                ->with('chunky.validation.index.key', null)
                ->andReturn('key');
            $mock->shouldReceive('get')
                ->once()
                ->with('chunky.validation.index.rules', [])
                ->andReturn([]);
        });
        $this->app->bind('config', function () use ($mock) {
            return $mock;
        });

        $request = new AddChunkRequest();
        $result = $request->indexRules();

        $this->assertEquals([
            'key' => ['required', 'integer', 'min:0'],
        ], $result);
    }

    /** @test */
    public function requestRetrievesIndexRules()
    {
        $mock = $this->mock(Repository::class, function ($mock) {
            $mock->shouldReceive('get')
                ->once()
                ->with('chunky.validation.index.key', null)
                ->andReturn('key');
            $mock->shouldReceive('get')
                ->once()
                ->with('chunky.validation.index.rules', [])
                ->andReturn(['required', 'foo', 'bar']);
        });
        $this->app->bind('config', function () use ($mock) {
            return $mock;
        });

        $request = new AddChunkRequest();
        $result = $request->indexRules();
        $result['key'] = array_values($result['key']);

        $this->assertEquals([
            'key' => ['required', 'integer', 'min:0', 'foo', 'bar'],
        ], $result);
    }

    /** @test */
    public function requestThrowExceptionOnMissingFileRules()
    {
        $mock = $this->mock(Repository::class, function ($mock) {
            $mock->shouldReceive('get')
                ->once()
                ->with('chunky.validation.file.key', null)
                ->andReturn(null);
            $mock->shouldReceive('get')
                ->once()
                ->with('chunky.validation.file.rules', [])
                ->andReturn([]);
        });
        $this->app->bind('config', function () use ($mock) {
            return $mock;
        });

        $request = new AddChunkRequest();

        $this->expectException(ChunkyException::class);

        $request->fileRules();
    }

    /** @test */
    public function requestRetrievesDefaultFileRules()
    {
        $mock = $this->mock(Repository::class, function ($mock) {
            $mock->shouldReceive('get')
                ->once()
                ->with('chunky.validation.file.key', null)
                ->andReturn('key');
            $mock->shouldReceive('get')
                ->once()
                ->with('chunky.validation.file.rules', [])
                ->andReturn([]);
        });
        $this->app->bind('config', function () use ($mock) {
            return $mock;
        });

        $request = new AddChunkRequest();
        $result = $request->fileRules();

        $this->assertEquals([
            'key' => ['required', 'file'],
        ], $result);
    }

    /** @test */
    public function requestRetrievesFileRules()
    {
        $mock = $this->mock(Repository::class, function ($mock) {
            $mock->shouldReceive('get')
                ->once()
                ->with('chunky.validation.file.key', null)
                ->andReturn('key');
            $mock->shouldReceive('get')
                ->once()
                ->with('chunky.validation.file.rules', [])
                ->andReturn(['required', 'foo', 'bar']);
        });
        $this->app->bind('config', function () use ($mock) {
            return $mock;
        });

        $request = new AddChunkRequest();
        $result = $request->fileRules();
        $result['key'] = array_values($result['key']);

        $this->assertEquals([
            'key' => ['required', 'file', 'foo', 'bar'],
        ], $result);
    }

    /** @test */
    public function requestThrowExceptionOnMissingChunkSizeRules()
    {
        $mock = $this->mock(Repository::class, function ($mock) {
            $mock->shouldReceive('get')
                ->once()
                ->with('chunky.validation.chunkSize.key', null)
                ->andReturn(null);
            $mock->shouldReceive('get')
                ->once()
                ->with('chunky.validation.chunkSize.rules', [])
                ->andReturn([]);
        });
        $this->app->bind('config', function () use ($mock) {
            return $mock;
        });

        $request = new AddChunkRequest();

        $this->expectException(ChunkyException::class);

        $request->chunkSizeRules();
    }

    /** @test */
    public function requestRetrievesDefaultChunkSizeRules()
    {
        $mock = $this->mock(Repository::class, function ($mock) {
            $mock->shouldReceive('get')
                ->once()
                ->with('chunky.validation.chunkSize.key', null)
                ->andReturn('key');
            $mock->shouldReceive('get')
                ->once()
                ->with('chunky.validation.chunkSize.rules', [])
                ->andReturn([]);
        });
        $this->app->bind('config', function () use ($mock) {
            return $mock;
        });

        $request = new AddChunkRequest();
        $result = $request->chunkSizeRules();

        $this->assertEquals([
            'key' => ['required', 'integer', 'min:1'],
        ], $result);
    }

    /** @test */
    public function requestRetrievesChunkSizeRules()
    {
        $mock = $this->mock(Repository::class, function ($mock) {
            $mock->shouldReceive('get')
                ->once()
                ->with('chunky.validation.chunkSize.key', null)
                ->andReturn('key');
            $mock->shouldReceive('get')
                ->once()
                ->with('chunky.validation.chunkSize.rules', [])
                ->andReturn(['required', 'foo', 'bar']);
        });
        $this->app->bind('config', function () use ($mock) {
            return $mock;
        });

        $request = new AddChunkRequest();
        $result = $request->chunkSizeRules();
        $result['key'] = array_values($result['key']);

        $this->assertEquals([
            'key' => ['required', 'integer', 'min:1', 'foo', 'bar'],
        ], $result);
    }

    public function request_throw_exception_on_missing_total_size_rules()
    {
        $mock = $this->mock(Repository::class, function ($mock) {
            $mock->shouldReceive('get')
                ->once()
                ->with('chunky.validation.totalSize.key', null)
                ->andReturn(null);
            $mock->shouldReceive('get')
                ->once()
                ->with('chunky.validation.totalSize.rules', [])
                ->andReturn([]);
        });
        $this->app->bind('config', function () use ($mock) {
            return $mock;
        });

        $request = new AddChunkRequest();

        $this->expectException(ChunkyException::class);

        $request->totalSizeRules();
    }

    /** @test */
    public function requestRetrievesDefaultTotalSizeRules()
    {
        $mock = $this->mock(Repository::class, function ($mock) {
            $mock->shouldReceive('get')
                ->once()
                ->with('chunky.validation.totalSize.key', null)
                ->andReturn('key');
            $mock->shouldReceive('get')
                ->once()
                ->with('chunky.validation.totalSize.rules', [])
                ->andReturn([]);
        });
        $this->app->bind('config', function () use ($mock) {
            return $mock;
        });

        $request = new AddChunkRequest();
        $result = $request->totalSizeRules();

        $this->assertEquals([
            'key' => ['required', 'integer', 'min:1'],
        ], $result);
    }

    /** @test */
    public function requestRetrievesTotalSizeRules()
    {
        $mock = $this->mock(Repository::class, function ($mock) {
            $mock->shouldReceive('get')
                ->once()
                ->with('chunky.validation.totalSize.key', null)
                ->andReturn('key');
            $mock->shouldReceive('get')
                ->once()
                ->with('chunky.validation.totalSize.rules', [])
                ->andReturn(['required', 'foo', 'bar']);
        });
        $this->app->bind('config', function () use ($mock) {
            return $mock;
        });

        $request = new AddChunkRequest();
        $result = $request->totalSizeRules();
        $result['key'] = array_values($result['key']);

        $this->assertEquals([
            'key' => ['required', 'integer', 'min:1', 'foo', 'bar'],
        ], $result);
    }

    /** @test */
    public function requestRetrievesDefaultAdditionalRules()
    {
        $mock = $this->mock(Repository::class, function ($mock) {
            $mock->shouldReceive('get')
                ->once()
                ->with('chunky.validation', null)
                ->andReturn([]);
        });
        $this->app->bind('config', function () use ($mock) {
            return $mock;
        });

        $request = new AddChunkRequest();
        $result = $request->additionalRules();

        $this->assertEmpty($result);
    }

    /** @test */
    public function requestRetrievesAdditionalFileRules()
    {
        $mock = $this->mock(Repository::class, function ($mock) {
            $mock->shouldReceive('get')
                ->once()
                ->with('chunky.validation', null)
                ->andReturn([
                    'index' => [],
                    'file' => [],
                    'folder' => [
                        'key' => 'folder',
                        'rules' => ['filled', 'string'],
                    ],
                ]);
        });
        $this->app->bind('config', function () use ($mock) {
            return $mock;
        });

        $request = new AddChunkRequest();
        $result = $request->additionalRules();

        $this->assertArrayHasKey('folder', $result);
        $this->assertEquals(['filled', 'string'], $result['folder']);
    }

    /** @test */
    public function requestReturnsRules()
    {
        $mock = $this->mock(Repository::class, function ($mock) {
            $mock->shouldReceive('get')
                ->once()
                ->with('chunky.validation.index.key', null)
                ->andReturn('index');
            $mock->shouldReceive('get')
                ->once()
                ->with('chunky.validation.index.rules', [])
                ->andReturn([]);

            $mock->shouldReceive('get')
                ->once()
                ->with('chunky.validation.file.key', null)
                ->andReturn('file');
            $mock->shouldReceive('get')
                ->once()
                ->with('chunky.validation.file.rules', [])
                ->andReturn([]);

            $mock->shouldReceive('get')
                ->once()
                ->with('chunky.validation.chunkSize.key', null)
                ->andReturn('chunkSize');
            $mock->shouldReceive('get')
                ->once()
                ->with('chunky.validation.chunkSize.rules', [])
                ->andReturn([]);

            $mock->shouldReceive('get')
                ->once()
                ->with('chunky.validation.totalSize.key', null)
                ->andReturn('totalSize');
            $mock->shouldReceive('get')
                ->once()
                ->with('chunky.validation.totalSize.rules', [])
                ->andReturn([]);

            $mock->shouldReceive('get')
                ->once()
                ->with('chunky.validation', null)
                ->andReturn([
                    'folder' => [
                        'key' => 'folder',
                        'rules' => ['foo', 'bar'],
                    ],
                ]);
        });
        $this->app->bind('config', function () use ($mock) {
            return $mock;
        });

        $request = new AddChunkRequest();
        $result = $request->rules();

        $this->assertArrayHasKey('index', $result);
        $this->assertArrayHasKey('file', $result);
        $this->assertArrayHasKey('chunkSize', $result);
        $this->assertArrayHasKey('totalSize', $result);
        $this->assertArrayHasKey('folder', $result);
        $this->assertEquals(['required', 'integer', 'min:0'], $result['index']);
        $this->assertEquals(['required', 'integer', 'min:1'], $result['chunkSize']);
        $this->assertEquals(['required', 'integer', 'min:1'], $result['totalSize']);
        $this->assertEquals(['required', 'file'], $result['file']);
        $this->assertEquals(['foo', 'bar'], $result['folder']);
    }

    /** @test */
    public function requestThrowsExceptionOnMissingIndexConfigKey()
    {
        $mock = $this->mock(Repository::class, function ($mock) {
            $mock->shouldReceive('get')
                ->once()
                ->with('chunky.validation.index.key', null)
                ->andReturn(null);
        });
        $this->app->bind('config', function () use ($mock) {
            return $mock;
        });

        $request = new AddChunkRequest();

        $this->expectException(ChunkyException::class);

        $request->indexInput();
    }

    /** @test */
    public function requestRetrievesIndexInput()
    {
        $mock = $this->mock(Repository::class, function ($mock) {
            $mock->shouldReceive('get')
                ->once()
                ->with('chunky.validation.index.key', null)
                ->andReturn('index');
        });
        $this->app->bind('config', function () use ($mock) {
            return $mock;
        });

        $request = AddChunkRequest::create('/', 'POST', ['index' => 0]);
        $result = $request->indexInput();

        $this->assertEquals(0, $result);
    }

    /** @test */
    public function requestThrowsExceptionOnMissingFileConfigKey()
    {
        $mock = $this->mock(Repository::class, function ($mock) {
            $mock->shouldReceive('get')
                ->once()
                ->with('chunky.validation.file.key', null)
                ->andReturn(null);
        });
        $this->app->bind('config', function () use ($mock) {
            return $mock;
        });

        $request = new AddChunkRequest();

        $this->expectException(ChunkyException::class);

        $request->fileInput();
    }

    /** @test */
    public function requestRetrievesFileInput()
    {
        $mock = $this->mock(Repository::class, function ($mock) {
            $mock->shouldReceive('get')
                ->once()
                ->with('chunky.validation.file.key', null)
                ->andReturn('file');
        });
        $this->app->bind('config', function () use ($mock) {
            return $mock;
        });

        $upload = UploadedFile::fake()->create('foo.mp4', 10000);

        $request = AddChunkRequest::create('/', 'POST', [], [], ['file' => $upload]);
        $result = $request->fileInput();

        $this->assertEquals($upload, $result);
    }

    /** @test */
    public function requestThrowsExceptionOnMissingChunkSizeConfigKey()
    {
        $mock = $this->mock(Repository::class, function ($mock) {
            $mock->shouldReceive('get')
                ->once()
                ->with('chunky.validation.chunkSize.key', null)
                ->andReturn(null);
        });
        $this->app->bind('config', function () use ($mock) {
            return $mock;
        });

        $request = new AddChunkRequest();

        $this->expectException(ChunkyException::class);

        $request->chunkSizeInput();
    }

    /** @test */
    public function requestRetrievesChunkSizeInput()
    {
        $mock = $this->mock(Repository::class, function ($mock) {
            $mock->shouldReceive('get')
                ->once()
                ->with('chunky.validation.chunkSize.key', null)
                ->andReturn('chunkSize');
        });
        $this->app->bind('config', function () use ($mock) {
            return $mock;
        });

        $request = AddChunkRequest::create('/', 'POST', ['chunkSize' => 2000]);
        $result = $request->chunkSizeInput();

        $this->assertEquals(2000, $result);
    }

    /** @test */
    public function requestThrowsExceptionOnMissingTotalSizeConfigKey()
    {
        $mock = $this->mock(Repository::class, function ($mock) {
            $mock->shouldReceive('get')
                ->once()
                ->with('chunky.validation.totalSize.key', null)
                ->andReturn(null);
        });
        $this->app->bind('config', function () use ($mock) {
            return $mock;
        });

        $request = new AddChunkRequest();

        $this->expectException(ChunkyException::class);

        $request->totalSizeInput();
    }

    /** @test */
    public function requestRetrievesTotalSizeInput()
    {
        $mock = $this->mock(Repository::class, function ($mock) {
            $mock->shouldReceive('get')
                ->once()
                ->with('chunky.validation.totalSize.key', null)
                ->andReturn('totalSize');
        });
        $this->app->bind('config', function () use ($mock) {
            return $mock;
        });

        $request = AddChunkRequest::create('/', 'POST', ['totalSize' => 10000]);
        $result = $request->totalSizeInput();

        $this->assertEquals(10000, $result);
    }
}
