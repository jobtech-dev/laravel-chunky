<?php

namespace Jobtech\LaravelChunky;

use Illuminate\Container\Container;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\ForwardsCalls;
use Jobtech\LaravelChunky\Exceptions\ChunkyException;
use Jobtech\LaravelChunky\Http\Resources\ChunkResource;
use Symfony\Component\HttpFoundation\File\File;

class Chunk implements Arrayable, Jsonable, Responsable
{
    use ForwardsCalls;

    /** @var bool */
    private $show_file_info = true;

    /** @var int */
    private $index;

    /** @var \Symfony\Component\HttpFoundation\File\File|string */
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

    private function sanitizeName(int $index)
    {
        return $index.'_'.Str::slug($this->getName()).'.'.$this->guessExtension();
    }

    /**
     * Retrieve the chunk index.
     *
     * @return int
     */
    public function getIndex(): int
    {
        return $this->index;
    }

    /**
     * Retrieve the chunk file path.
     *
     * @return \Symfony\Component\HttpFoundation\File\File|string
     */
    public function getPath(): string
    {
        if ($this->path instanceof File) {
            return $this->path->getRealPath();
        }

        return $this->path;
    }

    public function getFilename($suffix = null): string
    {
        if ($this->path instanceof UploadedFile) {
            return basename($this->path->getClientOriginalName(), $suffix);
        } elseif ($this->path instanceof File) {
            return $this->path->getBasename($suffix);
        }

        return basename($this->path, $suffix);
    }

    public function getName(): string
    {
        return pathinfo(
            $this->getFilename($this->guessExtension()),
            PATHINFO_FILENAME
        );
    }

    public function guessExtension()
    {
        if ($this->path instanceof File) {
            return $this->path->guessExtension();
        }

        return pathinfo($this->getFilename(), PATHINFO_EXTENSION);
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
     * Retrive file contents.
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function getFile()
    {
        if ($this->path instanceof File) {
            return $this->path;
        }

        $this->filesystem()
            ->get($this->path);
    }

    /**
     * Retrieve the chunk's filesystem and disk.
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     *
     * @return \Illuminate\Contracts\Filesystem\Filesystem
     */
    public function filesystem(): Filesystem
    {
        return Container::getInstance()->make(FilesystemFactory::class)
            ->disk($this->getDisk());
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
        $extension = $this->guessExtension();

        $data = [
            'name'      => $this->getName(),
            'extension' => $extension,
            'index'     => $this->getIndex(),
            'last'      => $this->isLast(),
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
        if ($request->wantsJson()) {
            return $this->toResource();
        }

        return new Response(
            $this->toJson(),
            Response::HTTP_CREATED
        );
    }

    /**
     * Transforms the current model into a json resource.
     */
    public function toResource()
    {
        /** @var \Illuminate\Http\Resources\Json\JsonResource $resource */
        $resource = config('chunky.resource', ChunkResource::class);

        return new $resource($this);
    }

    /**
     * Store the chunk into filesystem.
     *
     * @param string $folder
     * @param array $options
     *
     * @return Chunk
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function store(string $folder, $options = []): Chunk
    {
        if (! $this->path instanceof File) {
            throw new ChunkyException('Path must be a file');
        }

        $chunk_name = $this->sanitizeName($this->index);

        $path = $this->filesystem()
            ->putFileAs($folder, $this->path, $chunk_name, $options);

        return new static($this->getIndex(), $path, $this->getDisk(), $this->isLast());
    }

    /**
     * Store and return a new chunk instance.
     *
     * @param \Symfony\Component\HttpFoundation\File\File $file
     * @param string                                      $folder
     * @param int                                         $index
     * @param array                                       $options
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     *
     * @return \Jobtech\LaravelChunky\Chunk
     */
    public static function storeFrom(File $file, string $folder, int $index, $options = [])
    {
        $chunk = new static($index, $file, Arr::pull($options, 'disk'));

        return $chunk->store($folder, $options);
    }
}
