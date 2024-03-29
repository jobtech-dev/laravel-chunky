<?php

namespace Jobtech\LaravelChunky;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Traits\ForwardsCalls;
use Illuminate\Contracts\Support\Responsable;
use Symfony\Component\HttpFoundation\File\File;
use Illuminate\Http\Resources\Json\JsonResource;
use Jobtech\LaravelChunky\Http\Resources\ChunkResource;

class Chunk implements Arrayable, Jsonable, Responsable
{
    use ForwardsCalls;

    /** @var bool */
    private $show_file_info = true;

    /** @var int */
    private $index;

    /** @var string|\Symfony\Component\HttpFoundation\File\File */
    private $path;

    /** @var string|null */
    private $disk;

    /** @var bool */
    private $last;

    public function __construct(int $index, $path, $disk = null, $last = false)
    {
        $this->index = $index;
        $this->path = $path;
        $this->disk = $disk;
        $this->last = $last;
    }

    /**
     * @return int
     */
    public function getIndex(): int
    {
        return $this->index;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        if ($this->path instanceof File) {
            return $this->path->getRealPath();
        }

        return $this->path;
    }

    /**
     * @return mixed
     */
    public function getOriginalPath()
    {
        return $this->path;
    }

    /**
     * @param $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * @param string|null $suffix
     *
     * @return string
     */
    public function getFilename($suffix = null): string
    {
        if ($this->path instanceof UploadedFile) {
            return basename($this->path->getClientOriginalName(), $suffix);
        }

        if ($this->path instanceof File) {
            return $this->path->getBasename($suffix);
        }

        return basename($this->path, $suffix);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return pathinfo(
            $this->getFilename($this->getExtension()),
            PATHINFO_FILENAME
        );
    }

    /**
     * @return string|string[]
     */
    public function getExtension()
    {
        return pathinfo($this->getFilename(), PATHINFO_EXTENSION);
    }

    /**
     * @return string
     */
    public function getSlug(): string
    {
        return $this->index.'_'.Str::slug($this->getName()).'.'.$this->getExtension();
    }

    /**
     * Retrieve the chunk file disk.
     *
     * @return string|null
     */
    public function getDisk(): ?string
    {
        return $this->disk;
    }

    /**
     * Set the chunk file disk.
     *
     * @param string|null $disk
     */
    public function setDisk($disk = null)
    {
        $this->disk = $disk;
    }

    /**
     * @return bool
     */
    public function isLast(): bool
    {
        return $this->last;
    }

    /**
     * @param bool $last
     */
    public function setLast(bool $last): void
    {
        $this->last = $last;
    }

    /**
     * If this method is called, when a chunk is turned to array, the file path and real path
     * will be omitted.
     *
     * @return $this
     */
    public function hideFileInfo()
    {
        $this->show_file_info = false;

        return $this;
    }

    /**
     * If this method is called, when a chunk is turned to array, the file path and real path
     * will be included.
     *
     * @return $this
     */
    public function showFileInfo()
    {
        $this->show_file_info = true;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        $extension = $this->getExtension();

        $data = [
            'name' => $this->getName(),
            'extension' => $extension,
            'index' => $this->getIndex(),
            'last' => $this->isLast(),
        ];

        if ($this->show_file_info) {
            $data['file'] = $this->getFilename();
            $data['path'] = $this->getPath();
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * {@inheritdoc}
     */
    public function toResponse($request)
    {
        return $this->toResource()
            ->toResponse($request);
    }

    /**
     * Transforms the current model into a json resource.
     */
    public function toResource(): JsonResource
    {
        /** @var JsonResource $resource */
        $resource = config('chunky.resource', ChunkResource::class);

        return new $resource($this);
    }

    /**
     * @param string|\Symfony\Component\HttpFoundation\File\File $file
     * @param int                                                $index
     * @param array                                              $options
     *
     * @return Chunk
     */
    public static function create($file, int $index, $options = [])
    {
        return new static($index, $file, Arr::pull($options, 'disk'));
    }
}
