<?php

namespace Jobtech\LaravelChunky\Tests\Unit;

use Illuminate\Http\Request;
use Jobtech\LaravelChunky\Chunk;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Testing\File;
use Illuminate\Http\UploadedFile;
use Jobtech\LaravelChunky\Tests\TestCase;

/**
 * @internal
 */
class ChunkTest extends TestCase
{
    /**
     * @var File
     */
    private $upload;

    public function setUp(): void
    {
        parent::setUp();

        $this->upload = UploadedFile::fake()->image('foo.png');
    }

    public static function indexProvider()
    {
        return [
            [1],
            [2],
            [3],
            [10],
            [100],
            [1000],
        ];
    }

    /** @test */
    public function chunkHasAttributes()
    {
        $chunk = new Chunk(0, 'foo.ext', 'foo');

        $this->assertEquals(0, $chunk->getIndex());
        $this->assertEquals('foo.ext', $chunk->getPath());
        $this->assertEquals('foo', $chunk->getName());
        $this->assertEquals('ext', $chunk->getExtension());

        $this->assertEquals('foo', $chunk->getDisk());
        $chunk->setDisk('bar');
        $this->assertEquals('bar', $chunk->getDisk());

        $this->assertFalse($chunk->isLast());
        $chunk->setLast(true);
        $this->assertTrue($chunk->isLast());
    }

    /**
     * @test
     *
     * @dataProvider indexProvider
     *
     * @param $index
     */
    public function chunkHasAttributesFromFile($index)
    {
        $chunk = new Chunk($index, $this->upload);

        $this->assertEquals($index, $chunk->getIndex());
        $this->assertEquals($this->upload->getRealPath(), $chunk->getPath());
        $this->assertEquals('png', $extension = $chunk->getExtension());
        $this->assertEquals('foo', $chunk->getName());

        $this->assertNull($chunk->getDisk());
    }

    /** @test */
    public function chunkIsTransformedIntoAnArray()
    {
        $chunk = new Chunk(0, $this->upload);

        $result = [
            'file' => 'foo.png',
            'path' => $this->upload->getRealPath(),
            'name' => 'foo',
            'extension' => 'png',
            'index' => 0,
            'last' => false,
        ];

        $this->assertEquals($result, $chunk->toArray());
    }

    /** @test */
    public function chunkTogglesFileInfoWhenTransformedIntoAnArray()
    {
        $chunk = new Chunk(0, $this->upload);

        $result = [
            'name' => 'foo',
            'extension' => 'png',
            'index' => 0,
            'last' => false,
        ];

        $this->assertEquals($result, $chunk->hideFileInfo()->toArray());

        $result['file'] = 'foo.png';
        $result['path'] = $this->upload->getRealPath();

        $this->assertEquals($result, $chunk->showFileInfo()->toArray());
    }

    /** @test */
    public function chunkIsEncodedAsJson()
    {
        $chunk = new Chunk(0, $this->upload);
        $path = json_encode($this->upload->getRealPath());

        $result = '{"name":"foo","extension":"png","index":0,"last":false,"file":"foo.png","path":'.$path.'}';

        $this->assertEquals($result, $chunk->toJson());
    }

    /** @test */
    public function chunkTogglesFileInfoWhenIsEncodedAsJson()
    {
        $chunk = new Chunk(0, $this->upload);
        $path = json_encode($this->upload->getRealPath());
        $result = '{"name":"foo","extension":"png","index":0,"last":false}';

        $this->assertEquals($result, $chunk->hideFileInfo()->toJson());

        $result = '{"name":"foo","extension":"png","index":0,"last":false,"file":"foo.png","path":'.$path.'}';
        $this->assertEquals($result, $chunk->showFileInfo()->toJson());
    }

    /** @test */
    public function chunkIsTransformedIntoAJsonResource()
    {
        $mock = $this->mock(Request::class);
        $chunk = new Chunk(0, $this->upload);

        $result = $chunk->toResponse($mock);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(json_encode([
            'data' => $chunk->toArray(),
        ]), $result->getContent());
    }
}
