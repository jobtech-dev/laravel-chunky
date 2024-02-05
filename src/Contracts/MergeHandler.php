<?php

namespace Jobtech\LaravelChunky\Contracts;

use Jobtech\LaravelChunky\Http\Requests\AddChunkRequest;
use Illuminate\Contracts\Container\BindingResolutionException;

interface MergeHandler
{
    /**
     * @param ChunkyManager $manager
     *
     * @return \Jobtech\LaravelChunky\Handlers\MergeHandler
     */
    public function setManager(ChunkyManager $manager): MergeHandler;

    /**
     * @return ChunkyManager
     *
     * @throws BindingResolutionException
     */
    public function manager(): ChunkyManager;

    /**
     * @param AddChunkRequest $request
     * @param string          $folder
     *
     * @return \Illuminate\Foundation\Bus\PendingDispatch|string
     */
    public function dispatchMerge(AddChunkRequest $request, string $folder);

    /**
     * @param string $chunks_folder
     * @param string $merge_destination
     *
     * @return string
     */
    public function merge(string $chunks_folder, string $merge_destination): string;

    /**
     * @return MergeHandler
     */
    public static function instance(): MergeHandler;
}
