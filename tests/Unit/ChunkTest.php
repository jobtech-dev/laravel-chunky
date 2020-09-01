<?php

namespace Jobtech\LaravelChunky\Tests\Unit;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Jobtech\LaravelChunky\Chunk;
use Jobtech\LaravelChunky\Http\Resources\ChunkResource;
use Jobtech\LaravelChunky\Tests\TestCase;

class ChunkTest extends TestCase
{
    /**
     * @var \Illuminate\Http\Testing\File
     */
    private $upload;

    public function setUp(): void
    {
        parent::setUp();

        $this->upload = UploadedFile::fake()->image('foo.png');
    }

    public function indexProvider()
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
    public function chunk_has_attributes()
    {
        $chunk = new Chunk(0, 'foo.ext', 'foo');

        $this->assertEquals(0, $chunk->getIndex());
        $this->assertEquals('foo.ext', $chunk->getPath());
        $this->assertEquals('foo', $chunk->getName());
        $this->assertEquals('ext', $chunk->guessExtension());

        $this->assertEquals('foo', $chunk->getDisk());
        $chunk->setDisk('bar');
        $this->assertEquals('bar', $chunk->getDisk());

        $this->assertFalse($chunk->isLast());
        $chunk->setLast(true);
        $this->assertTrue($chunk->isLast());
    }

    /**
     * @test
     * @dataProvider indexProvider
     *
     * @param $index
     */
    public function chunk_has_attributes_from_file($index)
    {
        $chunk = new Chunk($index, $this->upload);

        $this->assertEquals($index, $chunk->getIndex());
        $this->assertEquals($this->upload->getRealPath(), $chunk->getPath());
        $this->assertEquals('png', $extension = $chunk->guessExtension());
        $this->assertEquals('foo', $chunk->getName());

        $this->assertNull($chunk->getDisk());
    }

    /** @test */
    public function chunk_is_stored_on_default_disk()
    {
        $chunk = new Chunk(0, $this->upload);
        $chunk->store('bar');

        Storage::assertExists('bar/0_foo.png');
    }

    /** @test */
    public function chunk_is_stored()
    {
        Storage::fake('foo');

        $chunk = new Chunk(0, $this->upload);
        $result = $chunk->store('bar', [
            'disk' => 'foo',
        ]);

        $this->assertNotEquals($chunk, $result);
        Storage::assertExists('bar/0_foo.png');
    }

    /** @test */
    public function chunk_is_transformed_into_an_array()
    {
        $chunk = new Chunk(0, $this->upload);

        $result = [
            'file' => 'foo.png',
            'path' => $this->upload->getRealPath(),
            'name' => 'foo',
            'extension' => 'png',
            'index'     => 0,
            'last'      => false,
        ];

        $this->assertEquals($result, $chunk->toArray());
    }

    /** @test */
    public function chunk_toggles_file_info_when_transformed_into_an_array()
    {
        $chunk = new Chunk(0, $this->upload);

        $result = [
            'name' => 'foo',
            'extension' => 'png',
            'index'     => 0,
            'last'      => false,
        ];

        $this->assertEquals($result, $chunk->hideFileInfo()->toArray());

        $result['file'] = 'foo.png';
        $result['path'] = $this->upload->getRealPath();

        $this->assertEquals($result, $chunk->showFileInfo()->toArray());
    }

    /** @test */
    public function chunk_is_encoded_as_json()
    {
        $chunk = new Chunk(0, $this->upload);
        $path = json_encode($this->upload->getRealPath());

        $result = '{"name":"foo","extension":"png","index":0,"last":false,"file":"foo.png","path":'.$path.'}';

        $this->assertEquals($result, $chunk->toJson());
    }

    /** @test */
    public function chunk_toggles_file_info_when_is_encoded_as_json()
    {
        $chunk = new Chunk(0, $this->upload);
        $path = json_encode($this->upload->getRealPath());
        $result = '{"name":"foo","extension":"png","index":0,"last":false}';

        $this->assertEquals($result, $chunk->hideFileInfo()->toJson());

        $result = '{"name":"foo","extension":"png","index":0,"last":false,"file":"foo.png","path":'.$path.'}';
        $this->assertEquals($result, $chunk->showFileInfo()->toJson());
    }

    /** @test */
    public function chunk_is_transformed_into_a_response()
    {
        $mock = $this->mock(Request::class, function ($mock) {
            $mock->shouldReceive('wantsJson')
               ->once()
               ->andReturn(false);
        });
        $chunk = new Chunk(0, $this->upload);

        /** @var Response $result */
        $result = $chunk->toResponse($mock);

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals($chunk->toJson(), $result->getContent());
    }

    /** @test */
    public function chunk_is_transformed_into_a_json_resource()
    {
        $mock = $this->mock(Request::class, function ($mock) {
            $mock->shouldReceive('wantsJson')
                ->once()
                ->andReturn(true);
        });
        $chunk = new Chunk(0, $this->upload);

        /** @var ChunkResource $result */
        $result = $chunk->toResponse($mock);

        $this->assertInstanceOf(ChunkResource::class, $result);
        $this->assertEquals(json_encode([
            'data' => $chunk->toArray(),
        ]), $result->toResponse($mock)->getContent());
    }

    /** @test */
    public function chunk_stores_from_file_in_default_disk()
    {
        Chunk::storeFrom($this->upload, 'bar', 0);

        Storage::assertExists('bar/0_foo.png');
    }

    /** @test */
    public function chunk_stores_from_file()
    {
        Storage::fake('foo');

        Chunk::storeFrom($this->upload, 'bar', 0, [
            'disk' => 'foo',
        ]);

        Storage::disk('foo')->assertExists('bar/0_foo.png');
    }
}
