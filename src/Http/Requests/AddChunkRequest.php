<?php

namespace Jobtech\LaravelChunky\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Jobtech\LaravelChunky\Exceptions\ChunkyException;

class AddChunkRequest extends FormRequest
{
    /**
     * Retrieve index rules.
     *
     * @return array
     */
    public function indexRules() : array {
        $key = config('chunky.validation.index.key');
        $rules = array_unique(
            array_merge([
                'required', 'integer', 'min:0'
            ], config('chunky.validation.index.rules', []))
        );

        if(empty($key)) {
            throw new ChunkyException("Index key cannot be null");
        }

        return [
            $key => $rules
        ];
    }

    /**
     * Retrieve file rules.
     *
     * @return array
     */
    public function fileRules() : array {
        $key = config('chunky.validation.file.key');
        $rules = array_unique(
            array_merge([
                'required', 'file'
            ], config('chunky.validation.file.rules', []))
        );

        if(empty($key)) {
            throw new ChunkyException("File key cannot be null");
        }

        return [
            $key => $rules
        ];
    }

    /**
     * Retrieve chunk file size rules.
     *
     * @return array
     */
    public function chunkSizeRules() : array {
        $key = config('chunky.validation.chunkSize.key');
        $rules = array_unique(
            array_merge([
                'required', 'integer', 'min:1'
            ], config('chunky.validation.chunkSize.rules', []))
        );

        if(empty($key)) {
            throw new ChunkyException("Chunk file size key cannot be null");
        }

        return [
            $key => $rules
        ];
    }

    /**
     * Retrieve total file size rules.
     *
     * @return array
     */
    public function totalSizeRules() : array {
        $key = config('chunky.validation.totalSize.key');
        $rules = array_unique(
            array_merge([
                'required', 'integer', 'min:1'
            ], config('chunky.validation.totalSize.rules', []))
        );

        if(empty($key)) {
            throw new ChunkyException("Total file size key cannot be null");
        }

        return [
            $key => $rules
        ];
    }

    public function additionalRules() : array {
        $rules = [];

        foreach(config('chunky.validation') as $input => $config) {
            if(
                !in_array($input, ['index', 'file', 'chunkSize', 'totalSize'])
                && Arr::has($config, 'key')
                && Arr::has($config, 'rules')
            ) {
                $rules[$config['key']] = Arr::get($config, 'rules', []);
            }
        }

        return $rules;
    }

    /**
     * Retrieve index input.
     *
     * @return int
     */
    public function indexInput() : int {
        $key = config('chunky.validation.index.key');

        if(empty($key)) {
            throw new ChunkyException("Index key cannot be null");
        }

        return $this->input($key);
    }

    /**
     * Retrieve file input.
     *
     * @return \Illuminate\Http\UploadedFile
     */
    public function fileInput() : UploadedFile {
        $key = config('chunky.validation.file.key');

        if(empty($key)) {
            throw new ChunkyException("File key cannot be null");
        }

        return $this->file($key);
    }

    /**
     * Retrieve chunk file size input.
     *
     * @return int
     */
    public function chunkSizeInput() : int {
        $key = config('chunky.validation.chunkSize.key');

        if(empty($key)) {
            throw new ChunkyException("Chunk file size key cannot be null");
        }

        return $this->input($key);
    }

    /**
     * Retrieve total file size input.
     *
     * @return int
     */
    public function totalSizeInput() : int {
        $key = config('chunky.validation.totalSize.key');

        if(empty($key)) {
            throw new ChunkyException("Total file size key cannot be null");
        }

        return $this->input($key);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return array_merge(
            $this->additionalRules(),
            $this->indexRules(),
            $this->fileRules(),
            $this->chunkSizeRules(),
            $this->totalSizeRules()
        );
    }
}