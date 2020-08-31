<br />
<p align="center">
  <a href="https://github.com/jobtech-dev/laravel-chunky">
    <img src="https://jobtech.it/img/logo.png" alt="Logo">
  </a>

  <h3 align="center">Laravel Chunky</h3>

  <p align="center">
    This package handles chunked files upload requests in order to safely save chunked files and, once the upload has been completed, merge all the chunks into a single file.
    <br />
    <br />
    <a href="https://github.com/jobtech-dev/laravel-chunky/issues">Report Bug</a>
    ·
    <a href="https://github.com/jobtech-dev/laravel-chunky/issues">Request Feature</a>
  </p>
</p>

[![MIT License](https://img.shields.io/github/license/jobtech-dev/laravel-chunky.svg?style=flat-square)](https://github.com/jobtech-dev/laravel-chunky/blob/master/LICENSE.txt)
[![Build status](https://github.com/jobtech-dev/laravel-chunky/workflows/tests/badge.svg)](https://github.com/jobtech-dev/laravel-chunky/actions)
[![GitHub stars](https://img.shields.io/github/stars/jobtech-dev/laravel-chunky)](https://github.com/jobtech-dev/laravel-chunky/stargazers)
[![GitHub issues](https://img.shields.io/github/issues/jobtech-dev/laravel-chunky)](https://github.com/jobtech-dev/laravel-chunky/issues)
[![LinkedIn](https://img.shields.io/badge/-LinkedIn-black.svg?style=flat-square&logo=linkedin&colorB=555)](https://linkedin.com/in/jobtech-srl)

## Table of Contents

* [Getting Started](#getting-started)
  * [Main features](#main-features)
  * [Prerequisites](#prerequisites)
  * [Installation](#installation)
* [Usage](#usage)
  * [Chunks](#chunks)
  * [Merge strategies](#merge-strategies)
* [Testing](#testing)
* [Roadmap](#roadmap)
* [Contributing](#contributing)
* [License](#license)
* [Contact](#contact)
* [Credits](#credits)

## Getting Started

Laravel chunky has been written to easily handle chunk upload for large files in Laravel 6.x and 7.x. It will automatically handle the upload request (see the [usage](#usage) section below) and save all the chunks into the desired disk.

Once the upload completes, the package will dispatch a job in order to merge all the files into a single one and save in the same chunks disks or in another one.

#### Main features
* Handle chunks upload with custom save disks and folders.
* Handle file merge with custom save disks and folders.
* Different merge strategies based on the file mime type.
* Once the merge is done, the chunks folder is automatically cleared.

### Prerequisites

This packages uses the [Laravel FFMpeg](https://github.com/protonemedia/laravel-ffmpeg) library, a wrapper around [php-ffmpeg](https://github.com/PHP-FFMpeg/PHP-FFMpeg) to merge video and audio files but it requires the ffmpeg library installed. 

From the [php-ffmpeg docs](https://github.com/PHP-FFMpeg/PHP-FFMpeg#how-this-library-works):

> This library requires a working FFMpeg install. You will need both FFMpeg and FFProbe binaries to use it. Be sure that these binaries can be located with system PATH to get the benefit of the binary detection, otherwise you should have to explicitly give the binaries path on load.
>
>  For Windows users: Please find the binaries at http://ffmpeg.zeranoe.com/builds/.

### Installation

In order to install Laravel Chunky into your project you have to require it via composer.

```sh
$ composer require jobtech-dev/laravel-chunky
```

Laravel uses Package Auto-Discovery and the `ChunkyServiceProvider` will be automatically registered. If you are using a Laravel version lower than 5.5 or you're not using autodiscovery, manually register the service provider:

```php
// config/app.php
[
  // [...]
  'providers' => [
      // [...]
      Jobtech\LaravelChunky\ChunkyServiceProvider::class,
  ]
];
```

You can also register an alias for the `Chunky` facade:

```php
// config/app.php
[
  // [...]
  'aliases' => [
      // [...]
      'Chunky' => Jobtech\LaravelChunky\ChunkyServiceProviderChunky::class,
  ]
];
```

#### Configuration

To publish the configuration file, run the following command:

```sh
$ php artisan vendor:publish --provider="Jobtech\LaravelChunky\ChunkyServiceProvider" --tag="config"
```

#### Lumen

This package can also work with Lumen, just register the service provider in `bootstrap/app.php`:

```php
$app->register(Jobtech\LaravelChunky\ChunkyServiceProvider::class);
```

In order to configure the package, since lumen doesn't include the `vendor:publis` command, copy the configuration file to your config folder and enable it:

```php
$app->configure('chunky');
```

## Usage

This package has been designed to leave you the full control of the chunks upload and simple use the helper methods to handle the merge strategy as well as an _all-in-one_ solution for a fast scaffolding of the controllers delegated to handle large files upload.

At the moment, this package doesn't include any wrapper for the frontend forms for the file uploads but, in the `config/chunky.php` configuration file, you can find two ways of integrate the package with [Dropzone](https://www.dropzonejs.com/) and [ResumableJs](http://resumablejs.com/).

### Chunks

Laravel Chunky handles the chunks as an _ordered list_ of files. This is a **must** and if a wrong file index has been uploaded, an exception will be thrown in order to guarantee the integrity of the final merged file. Once all the chunks have been uploaded, and the merge strategy is executing, another integrity check will be made to all the chunks. If the sum of each file size is lower than the original file size, another exception will be thrown. For this reason a chunk request must include both the chunk and these attributes:

* An `index`: indicates the current chunk that is uploading. The first index can be set in the configuration file.
* A `file size`: the original file size. Will be used for the integrity check.
* A `chunk size`: the chunk file size. Will be used for the integrity check.

#### Configuration

```php
// config/chunky.php
[

    /*
    |--------------------------------------------------------------------------
    | Default disks
    |--------------------------------------------------------------------------
    |
    | This option defines the disks on which to store the chunks from an upload
    | request as well as the final merged file. If you don't need to save the
    | files into a sub folder just set null as value.
    |
    */

    'disks' => [
        'chunks' => [
            'disk'   => env('CHUNKY_CHUNK_DISK'),
            'folder' => 'chunks',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default index
    |--------------------------------------------------------------------------
    |
    | This option defines if chunky should start counting the chunks indexes
    | from 0 (ChunkySettings::INDEX_ZERO) or 1 (ChunkySettings::INDEX_ONE). You
    | can override this feature with any number, but the indexes must always
    | be index + n or the integrity check for the chunks folder will throw an
    | exception.
    |
    */

    'index' => \Jobtech\LaravelChunky\ChunkySettings::INDEX_ZERO,

    /*
    |--------------------------------------------------------------------------
    | Additional options
    |--------------------------------------------------------------------------
    |
    | This option defines the additional settings that chunky should pass to
    | the `storeAs` method while saving chunks or the merged file. This can be
    | useful, for example, when storing public files in S3 storage.
    |
    */

    'options' => [
        'chunks' => [
            // 'visibility' => 'public'
        ],
    ],
];
```

#### Chunks methods

If you want to manually save a chunk from a request you can use the `addChunk` method. It gets in input the uploaded file, the chunk index and, optionally, a folder name. If no folder is passed, the chunks anyway will be stored into a chunk's root subfolder. This folder will be named as the slug of the uploaded file basename.

This method will return a `Jobtech\LaravelChunky\Chunk` object, that implements the `Illuminate\Contracts\Support\Responsable` contract so you can easily return a JSON response. If the requests has the `Accept application/json` header, the object will be automatically transformed into a `Jobtech\LaravelChunky\Http\Resources\ChunkResource` object.

```php
// ...
$chunk = Chunky::addChunk(
    $request->file('your-file-key'),
    $request->input('your-index-key'),
    'folder-is-optional'
); 

return $chunk->hideFileInfo()->toResponse();
// This will return
// {
//   "data": {
//     "name": "my-very-big-file.ext",
//     "extension": "ext",
//     "index": 0,
//     "last": false
//   }
// }

return $chunk->showFileInfo()->toResponse();
// This will return
// {
//   "data": {
//     "name": "my-very-big-file.ext",
//     "extension": "ext",
//     "index": 0,
//     "last": false,
//     "file": "/path/to/my-very-big-file.ext",
//     "path": "/path/to"
//   }
// }
```

> If you're trying to add a chunk that violates the integrity of the chunks folder an exception will be thrown.
> for example:
> 
>```php
> |- chunks
>    |- folder
>       |- 0_chunk.ext
>       |- 1_chunk.ext
>       |- 2_chunk.ext
> 
> Chunk::addChunk($chunk, 4); 
>```
> This will throw a `Jobtech\LaravelChunky\Exceptions\ChunksIntegrityException`


If you're using, for example, Dropzone you can block the upload action, in that case you will delete the currently uploaded chunks:

```php
Chunky::deleteChunk('chunks-folder');
``` 

The package include a method that, given the chunks folder, will return a sorted collection. Each item contains the relative chunk's path and index.

```php
$chunks = Chunky::chunk('chunks-folder-name');

foreach($chunks as $chunk) {
  print_r($chunk);
}

//  [
//    'index' => 0,
//    'path'  => '/path/to/0_chunk.ext'
//  ],
//  [
//    'index' => 1,
//    'path'  => '/path/to/1_chunk.ext'
//  ],
//  [
//    'index' => 2,
//    'path'  => '/path/to/2_chunk.ext'
//  ],
//  ...
```

#### Chunks request

If you want to automate the chunks upload and merge (requires the value `true` for the `auto_merge` config key), you can use the `Jobtech\LaravelChunky\Http\Requests\AddChunkRequest` class. For more informations about form request please have a look at the [official documentation](https://laravel.com/docs/6.x/validation#form-request-validation).

Include the form request in your method and simply call the `handle` method of the Chunky facade. The package will automatically handle the upload and return a `Jobtech\LaravelChunky\Chunk` object.

```php
// Example with `auto_merge = false`

use Jobtech\LaravelChunky\Http\Requests\AddChunkRequest;

class UploadController extends Controller {
    // [...]

    public function chunkUpload(AddChunkRequest $request) {
       $chunk = Chunky::handle($request, 'folder-is-optional');

       if($chunk->isLast()) {
           // See section below for merge or 
           // implement your own logic
       }

       return $chunk->toResponse();
    }
}
```

### Merge strategies

If you need to merge chunks into a single file, you can use the strategies to merge the uploaded chunks into a single file. If the `auto_merge` config key is `true`, the package will automatically decide which merge strategy should be used depending on the file mime type. If no strategy has been declared, the default one will be used.

Once the last chunk has been uploaded, a `Jobtech\LaravelChunky\Jobs\MergeChunks` job will be dispatched on the given connection and queue.

```php
// config/chunky.php
[
   // [...]

   /*
    |--------------------------------------------------------------------------
    | Merge strategies
    |--------------------------------------------------------------------------
    |
    | This option defines the strategy that should be used for a given file
    | mime type. If left empty, the default strategy will be used.
    |
    | `connection` and `queue` keys define which queue and which connection
    | should be used for the merge job. If connection is null, a synchronous
    | job will be dispatched
    */

    'strategies' => [
        'default' => \Jobtech\LaravelChunky\Strategies\FlysystemStrategy::class,

        'mime_types' => [
            'video/*' => \Jobtech\LaravelChunky\Strategies\VideoStrategy::class,
            'audio/*' => \Jobtech\LaravelChunky\Strategies\AudioStrategy::class,
        ],

        'connection' => env('CHUNKY_MERGE_CONNECTION', 'default'),

        'queue' => env('CHUNKY_MERGE_QUEUE'),
    ],
];
```

You can manually dispatch the job (or if you're not using the `Jobtech\LaravelChunky\Http\Requests\AddChunkRequest` form request create your own):

```php
use Jobtech\LaravelChunky\Http\Requests\AddChunkRequest;
use Jobtech\LaravelChunky\Jobs\MergeChunks;

class UploadController extends Controller {
    // [...]

    public function chunkUpload(AddChunkRequest $request) {
       $chunk = Chunky::handle($request, 'folder-is-optional');

       if($chunk->isLast()) {
            $job = new MergeChunks($request, 'chunks-folder', 'destination/path/to/merge.ext');

            dispatch(
                $job->onConnection('your-connection')
                    ->onQueue('your-queue')
            );
       }

       return $chunk->toResponse();
    }
}
```

#### Custom strategies

If you prefer to do everything on your own, without dispatching jobs but simply using the strategy:

```php
use Jobtech\LaravelChunky\Http\Requests\AddChunkRequest;
use Jobtech\LaravelChunky\Handlers\MergeHandler;

class UploadController extends Controller {
    // [...]

    public function chunkUpload(AddChunkRequest $request) {
       $chunk = Chunky::handle($request, 'folder-is-optional');

       if($chunk->isLast()) {
            $strategy = MergeHandler::strategyBy($request->file('chunk')->getMimeType());

            $strategy->chunksFolder('chunks-folder');
            $strategy->destination('destination/path/to/merge.ext');

            $strategy->merge();
       }

       return $chunk->toResponse();
    }
}       
```

Finally, you can implement your own strategy by extending the `Jobtech\LaravelChunky\Strategies\MergeStrategy` abstract class. This class implements the `Illuminate\Support\Traits\ForwardsCalls` trait, so you can call all the helpers methods of the `Jobtech\LaravelChunky\ChunksManager` class.

> At the moment only the `Jobtech\LaravelChunky\Strategies\FlysystemStrategy` and the `Jobtech\LaravelChunky\Strategies\VideoStrategy` have been implemented. If you want to add more strategy, please follow the [contributing](#contributing) section.

This is a scaffolding of a custom strategy:

```php
<?php

namespace App\MergeStrategies;

use Jobtech\LaravelChunky\Strategies\MergeStrategy;
use Jobtech\LaravelChunky\Strategies\Concerns\ChecksIntegrity;

class PDFStrategy extends MergeStrategy
{
    use ChecksIntegrity;

    /**
     * @inheritDoc
     */
    public function merge()
    {
        // Implement here your logic to merge pdf chunks
    }
}
```

Once completed, add your strategy into the configuration file, so you can automatically resolve your strategy with the `Jobtech\LaravelChunky\Handlers\MergeHandler::strategyBy` method:


```php
// config/chunky.php
[
    // [...]

    'strategies' => [
        'default' => \Jobtech\LaravelChunky\Strategies\FlysystemStrategy::class,

        'mime_types' => [
            'video/*' => \Jobtech\LaravelChunky\Strategies\VideoStrategy::class,
            'audio/*' => \Jobtech\LaravelChunky\Strategies\AudioStrategy::class,
            // Add here
            'application/pdf' => \App\MergeStrategies\PDFStrategy::class,
        ],
    ],

    // [...]
];
```

## Testing

You can run the tests with PHP unit:

```sh
$ vendor/bin/phpunit
```

If you want to set custom environment variable, you can add a `.env` file for custom disks, queue or whatever you need. Tests anyway set a temporary local disk by default.

```
CHUNKY_CHUNK_DISK=s3
CHUNKY_MERGE_DISK=public
CHUNKY_AUTO_MERGE=false
CHUNKY_MERGE_CONNECTION=redis
CHUNKY_MERGE_QUEUE=my-custom-queue
``` 

## Roadmap

See the [open issues](https://github.com/jobtech-dev/laravel-chunky/issues) for a list of proposed features (and known issues).

We're working on:

* Implement more merge strategies for specific mime types
  * Video and audio should have dedicated strategy for each codec.
* Integrate frontend chunk upload (Not sure if necessary... there are so many packages that does it)
* Better tests
* Laravel 5.5+ compatibility

## Contributing

Please see [CONTRIBUTING.md](https://github.com/jobtech-dev/laravel-chunky/blob/master/CONTRIBUTING.md) for more details.

## License

Distributed under the MIT License. See [LICENSE](https://github.com/jobtech-dev/laravel-chunky/blob/master/LICENSE) for more information.

## Contact

Jobtech dev team - [dev@jobtech.it](mailto:dev@jobtech.it)

## Credits

Laravel Chunky is a Laravel package made with :heart: by the JT nerds.

Thanks to:

* Filippo Galante ([ilGala](https://github.com/ilgala)) 
* [All contributors](https://github.com/jobtech-dev/laravel-chunky/graphs/contributors)

We've used these packages for the merge strategies:

* Keven Godet - [Flysystem concatenate](https://github.com/kevengodet/flysystem-concatenate)
* Protone Media - [Laravel FFMpeg](https://github.com/protonemedia/laravel-ffmpeg)

And this repository for the readme boilerplate:

* Othneil Drew - [Best-README-Template](https://github.com/othneildrew/Best-README-Template)