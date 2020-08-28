<?php

namespace Jobtech\LaravelChunky;

use Illuminate\Support\Facades\Facade;

class Chunky extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'chunky';
    }
}