<?php

namespace Jobtech\LaravelChunky\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ChunkResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->resource->toArray();
    }
}