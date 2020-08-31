<?php

namespace Jobtech\LaravelChunky;

use Illuminate\Container\Container;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\ForwardsCalls;
use Jobtech\LaravelChunky\Http\Resources\ChunkResource;
use Symfony\Component\HttpFoundation\File\File;

/**
 * @mixin \Symfony\Component\HttpFoundation\File\File
 */
class Chunk implements Arrayable, Jsonable, Responsable
{
    use ForwardsCalls;

    /** @var bool */
    private $show_file_info = true;

    /** @var int */
    private $index;

    /** @var \Symfony\Component\HttpFoundation\File\File */
    private $file;

    /** @var bool */
    private $last;

    public function __construct(int $index, File $file, $last = false)
    {
        $this->index = $index;
        $this->file = $file;
        $this->last = $last;
    }

    private function sanitizeName(int $index, string $file_name, string $extension)
    {
        return $index.'_'.Str::slug($file_name).'.'.$extension;
    }

    /**
     * Retrieve the chunk original file upload.
     *
     * @return \Symfony\Component\HttpFoundation\File\File|null
     */
    public function getFile(): ?File
    {
        return $this->file;
    }

    /**
     * Retrieve the chunk original file upload.
     *
     * @param \Symfony\Component\HttpFoundation\File\File $file
     */
    public function setFile(File $file)
    {
        $this->file = $file;
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
     * Store the chunk into filesystem.
     *
     * @param string $folder
     * @param array  $options
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     *
     * @return Chunk
     */
    public function storeIn(string $folder, $options = []): Chunk
    {
        // Get extension
        $extension = $this->guessExtension();

        // Check file instance and retrieve file name
        if ($this->file instanceof UploadedFile) {
            $file_name = str_replace(
                $extension,
                '',
                $this->file->getClientOriginalName()
            );
        } else {
            $file_name = $this->file->getBasename(
                $extension
            );
        }

        // Sanitize name
        $chunk_name = $this->sanitizeName(
            $this->index,
            $file_name,
            $extension
        );

        // Store file
        $disk = Arr::pull($options, 'disk');
        $path = Container::getInstance()->make(FilesystemFactory::class)
            ->disk($disk)
            ->putFileAs(
                $folder,
                $this->file,
                $chunk_name,
                $options
            );

        return new static($this->getIndex(), new File($path, false));
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        $extension = $this->file->guessExtension();

        $data = [
            'name'      => $this->getBasename($extension),
            'extension' => $extension,
            'index'     => $this->getIndex(),
            'last'      => $this->isLast(),
        ];

        if ($this->show_file_info) {
            $data['file'] = $this->getRealPath();
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
            return $this->toResource($request);
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

    public function __call($method, $parameters)
    {
        if (! method_exists($this, $method)) {
            return $this->forwardCallTo($this->file, $method, $parameters);
        }

        return $this->{$method}(...$parameters);
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
        $chunk = new static($index, $file);

        return $chunk->storeIn($folder, $options);
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
}
