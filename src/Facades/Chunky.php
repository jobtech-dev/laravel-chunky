<?php

namespace Jobtech\LaravelChunky\Facades;

use Illuminate\Support\Facades\Facade;

class Chunky extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'chunky';
    }
}
