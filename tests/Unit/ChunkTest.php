<?php

namespace Jobtech\LaravelChunky\Tests\Unit;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Jobtech\LaravelChunky\Chunk;
use Jobtech\LaravelChunky\Http\Resources\ChunkResource;
use Jobtech\LaravelChunky\Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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

    public function indexProvider() {
        return [
            [1],
            [2],
            [3],
            [10],
            [100],
            [1000],
        ];
    }

    /**
     * @test
     * @dataProvider indexProvider
     * @param $index
     */
    public function chunk_has_attributes($index) {
        $chunk = new Chunk($index, $this->upload);

        $this->assertEquals($this->upload, $chunk->getFile());
        $this->assertEquals($index, $chunk->getIndex());
        $this->assertFalse($chunk->isLast());

        $chunk->setLast(true);
        $this->assertTrue($chunk->isLast());
    }

    /** @test */
    public function chunk_is_stored_on_default_disk() {
        $chunk = new Chunk(0, $this->upload);
        $chunk->storeIn('bar');

        Storage::assertExists('bar/0_foo.png');
    }

    /** @test */
    public function chunk_is_stored() {
        Storage::fake('foo');

        $chunk = new Chunk(0, $this->upload);
        $result = $chunk->storeIn('bar', [
            'disk' => 'foo'
        ]);

        $this->assertNotEquals($chunk, $result);
        Storage::disk('foo')->assertExists('bar/0_foo.png');
    }

    /** @test */
    public function chunk_is_transformed_into_an_array() {
        $chunk = new Chunk(0, $this->upload);
        $file = $this->upload->getRealPath();

        $result = [
            "file" => $file,
            "path" => $this->upload->getPath(),
            "name" => $this->upload->getBasename(
                $this->upload->guessClientExtension()
            ),
            "extension" => "png",
            "index" => 0,
            "last" => false
        ];

        $this->assertEquals($result, $chunk->toArray());
    }

    /** @test */
    public function chunk_toggles_file_info_when_transformed_into_an_array() {
        $chunk = new Chunk(0, $this->upload);
        $file = $this->upload->getRealPath();

        $result = [
            "name" => $this->upload->getBasename(
                $this->upload->guessClientExtension()
            ),
            "extension" => "png",
            "index" => 0,
            "last" => false
        ];

        $this->assertEquals($result, $chunk->hideFileInfo()->toArray());

        $result["file"] = $file;
        $result["path"] = $this->upload->getPath();

        $this->assertEquals($result, $chunk->showFileInfo()->toArray());
    }

    /** @test */
    public function chunk_is_encoded_as_json() {
        $chunk = new Chunk(0, $this->upload);
        $file = json_encode($this->upload->getRealPath());
        $path = json_encode($this->upload->getPath());
        $name = json_encode($this->upload->getBasename(
            $this->upload->guessClientExtension()
        ));

        $result = '{"name":'.$name.',"extension":"png","index":0,"last":false,"file":'.$file.',"path":'.$path.'}';

        $this->assertEquals($result, $chunk->toJson());
    }

    /** @test */
    public function chunk_toggles_file_info_when_is_encoded_as_json() {
        $chunk = new Chunk(0, $this->upload);
        $file = json_encode($this->upload->getRealPath());
        $path = json_encode($this->upload->getPath());
        $name = json_encode($this->upload->getBasename(
            $this->upload->guessClientExtension()
        ));

        $result = '{"name":'.$name.',"extension":"png","index":0,"last":false}';

        $this->assertEquals($result, $chunk->hideFileInfo()->toJson());

        $result = '{"name":'.$name.',"extension":"png","index":0,"last":false,"file":'.$file.',"path":'.$path.'}';
        $this->assertEquals($result, $chunk->showFileInfo()->toJson());
    }

    /** @test */
    public function chunk_is_transformed_into_a_response() {
        $mock = $this->mock(Request::class, function($mock) {
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
    public function chunk_is_transformed_into_a_json_resource() {
        $mock = $this->mock(Request::class, function($mock) {
            $mock->shouldReceive('wantsJson')
                ->once()
                ->andReturn(true);
        });
        $chunk = new Chunk(0, $this->upload);

        /** @var ChunkResource $result */
        $result = $chunk->toResponse($mock);

        $this->assertInstanceOf(ChunkResource::class, $result);
        $this->assertEquals(json_encode([
            'data' => $chunk->toArray()
        ]), $result->toResponse($mock)->getContent());
    }

    /** @test */
    public function chunck_forwards_calls_to_uploaded_file() {
        $chunk = new Chunk(0, $this->upload);

        $this->assertEquals($this->upload->getPath(), $chunk->getPath());
        $this->assertEquals($this->upload->getFilename(), $chunk->getFilename());
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
            'disk' => 'foo'
        ]);

        Storage::disk('foo')->assertExists('bar/0_foo.png');
    }
}