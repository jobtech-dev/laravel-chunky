<br />
<p align="center">
  <a href="https://github.com/jobtech-dev/laravel-chunky">
    <img src="https://user-images.githubusercontent.com/1577699/100456224-4ab28680-30c0-11eb-8452-e6a674f3dcdb.png" alt="Logo">
  </a>
  
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
[![StyleCI](https://github.styleci.io/repos/291024576/shield?branch=master)](https://github.styleci.io/repos/291024576?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/jobtech-dev/laravel-chunky/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/jobtech-dev/laravel-chunky/?branch=master)
[![GitHub stars](https://img.shields.io/github/stars/jobtech-dev/laravel-chunky)](https://github.com/jobtech-dev/laravel-chunky/stargazers)
[![GitHub issues](https://img.shields.io/github/issues/jobtech-dev/laravel-chunky)](https://github.com/jobtech-dev/laravel-chunky/issues)
[![LinkedIn](https://img.shields.io/badge/-LinkedIn-black.svg?style=flat-square&logo=linkedin&colorB=555)](https://linkedin.com/in/jobtech-srl)

## Table of Contents

* [Getting Started](#getting-started)
  * [Main features](#main-features)
  * [Installation](#installation)
* [Usage](#usage)
  * [Chunks](#chunks)
  * [Merge handler](#merge-handler)
* [Testing](#testing)
* [Roadmap](#roadmap)
* [Changelog](#changelog)
* [Contributing](#contributing)
* [License](#license)
* [Contact](#contact)
* [Credits](#credits)

## Laravel compatibility

 Laravel      | laravel-chunky
:-------------|:----------
10.x          | 3.0.0
 9.x          | 2.0.0
 8.x          | 1.4.1
 7.x          | 1.4.1
 6.x          | 1.4.1

## Getting Started

Laravel chunky is a package that can handle chunk upload for large files in Laravel 6.x, 7.x. and 8.x. Its main goal is automatically handle the upload request (see the [usage](#usage) section below) and save all the chunks into the desired disk.

Once the upload completes, the package will dispatch a job in order to merge all the files into a single one and save in the same chunks disks or in another one.

### Main features
* Handle chunks upload with custom save disks and folders.
* Handle file merge with custom save disks and folders.
* Once the merge is done, the chunks folder is automatically cleared.

### Installation

In order to install Laravel Chunky into your project you have to require it via composer.

```sh
$ composer require jobtech/laravel-chunky
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
      'Chunky' => Jobtech\LaravelChunky\Facades\Chunky::class,
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

In order to configure the package, since lumen doesn't include the `vendor:publish` command, copy the configuration file to your config folder and enable it:

```php
$app->configure('chunky');
```

## Usage

This package has been designed to leave you the full control of the chunks upload and simple use the helper methods to handle the files merge as well as an _all-in-one_ solution for a fast scaffolding of the controllers delegated to handle large files upload.

At the moment, this package doesn't include any wrapper for the frontend forms for the file uploads but, in the `config/chunky.php` configuration file, you can find two ways of integrate the package with [Dropzone](https://www.dropzonejs.com/) and [ResumableJs](http://resumablejs.com/).

### Chunks

Laravel Chunky handles the chunks as an _ordered list_ of files. This is a **must** and if a wrong file index has been uploaded, an exception will be thrown in order to guarantee the integrity of the final merged file. Once all the chunks have been uploaded, and the merge process is executing, another integrity check will be made to all the chunks. If the sum of each file size is lower than the original file size, another exception will be thrown. For this reason a chunk request must include both the chunk and these attributes:

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

This method will return a `Jobtech\LaravelChunky\Chunk` object, that implements the `Illuminate\Contracts\Support\Responsable` contract so you can easily return a JSON response. If the requests has the `Accept application/json` header, the object will be automatically transformed into a `Jobtech\LaravelChunky\Http\Resources\ChunkResource` object. Furthermore, every time a chunk is added a `Jobtech\LaravelChunky\Events\ChunkAdded` event is fired.

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

Everytime a chunk is added, a `Jobtech\LaravelChunky\Events\ChunkDeleted` event is fired.

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
Chunky::deleteChunks('chunks-folder');
``` 
Everytime a chunk is deleted, a `Jobtech\LaravelChunky\Events\ChunkDeleted` event is fired.

---

The package include a method that, given the chunks folder, will return a sorted collection. Each item contains the relative chunk's path and index.

```php
$chunks = Chunky::listChunks('chunks-folder-name');

foreach($chunks as $chunk) {
  /** @var \Jobtech\LaravelChunky\Chunk $chunk */
  print_r($chunk->toArray());
}

//  [
//    'index' => 0,
//    'path'  => '/path/to/chunks-folder-name/0_chunk.ext',
//    [...]
//  ],
//  [
//    'index' => 1,
//    'path'  => '/path/to/chunks-folder-name/1_chunk.ext',
//    [...]
//  ],
//  [
//    'index' => 2,
//    'path'  => '/path/to/chunks-folder-name/2_chunk.ext',
//    [...]
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

### Merge handler

If you need to merge chunks into a single file, you can call the `merge` function that will use the configured merge handler to concatenate all the uploaded chunks into a single file. 

```
public function chunkUpload(AddChunkRequest $request) {
   $chunk = Chunky::handle($request, 'folder-is-optional');

   if($chunk->isLast()) {
       Chunky::merge('upload-folder', 'your/merge/file.ext');
   }

   return $chunk->toResponse();
}
```

Once the last chunk has been uploaded and the `auto_merge` config key has `true` value, the package will automatically merge the chunks. A `Jobtech\LaravelChunky\Jobs\MergeChunks` job will be dispatched on the given connection and queue if these options have been set.

```php
// config/chunky.php
[
   // [...]

   /*
   |--------------------------------------------------------------------------
   | Merge settings
   |--------------------------------------------------------------------------
   |
   | This option defines the merge handler that should be used to perform the
   | chunks merge once the upload is completed (automagically depending on
   | `auto_merge` config value.
   |
   | `connection` and `queue` keys define which queue and which connection
   | should be used for the merge job. If connection is null, a synchronous
   | job will be dispatched
   */
   
   'merge' => [
       'handler' => \Jobtech\LaravelChunky\Handlers\MergeHandler::class,
   
       'connection' => env('CHUNKY_MERGE_CONNECTION', 'sync'),
   
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

Once the job is completed, a `Jobtech\LaravelChunky\Events\ChunksMerged` event is fired as well as once the merge file is moved to destination a `Jobtech\LaravelChunky\Events\MergeAdded` event is fired.

#### Custom handler

If you want to integrate your own handler, remember to implement the `Jobtech\LaravelChunky\Contracts\MergeHandler` contract (or at least implement the same methods) in your class, and update the related `handler` configuration option:

```php
use Jobtech\LaravelChunky\Contracts\MergeHandler;

class MyHandler implements MergeHandler {
    private ChunkyManager $manager;

    /**
     * @param \Jobtech\LaravelChunky\Contracts\ChunkyManager $manager
     * @return \Jobtech\LaravelChunky\Handlers\MergeHandler
     */
    public function setManager(ChunkyManager $manager): MergeHandler 
    {
        $this->manager = $manager;

        return $this;
    }
    
    /**
     * @return \Jobtech\LaravelChunky\Contracts\ChunkyManager
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function manager(): ChunkyManager
    {
        return $this->manager;
    }
    
    /**
     * @param \Jobtech\LaravelChunky\Http\Requests\AddChunkRequest $request
     * @param string $folder
     *
     * @return \Illuminate\Foundation\Bus\PendingDispatch|string
     */
    public function dispatchMerge(AddChunkRequest $request, string $folder)
    {
        // Your logic here
    }
    
    /**
     * @param string $chunks_folder
     * @param string $merge_destination
     *
     * @return string
     */
    public function merge(string $chunks_folder, string $merge_destination): string
    {
        // Your logic here
    }
    
    /**
     * @return \Jobtech\LaravelChunky\Contracts\MergeHandler
     */
    public static function instance(): MergeHandler
    {
        return new static();
    }
}       
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

* Integrate frontend chunk upload (Not sure if necessary... there are so many packages that does it).
* Custom concatenation, at the moment we're using a third party package.
* Better tests.
* Laravel 5.5+ compatibility.

## Changelog

Please see [CHANGELOG.md](https://github.com/jobtech-dev/laravel-chunky/blob/master/CHANGELOG.md) for more information what has changed recently.

## Contributing
This package comes with a docker container based on php 8.1 and composer 2.2. To start it simply run `make start`. To enter the container shell you can use `make shell`.

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

We've used these packages for the chunks concatenation:

* Keven Godet - [Flysystem concatenate](https://github.com/kevengodet/flysystem-concatenate)

And this repository for the readme boilerplate:

* Othneil Drew - [Best-README-Template](https://github.com/othneildrew/Best-README-Template)
