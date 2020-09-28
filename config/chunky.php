<?php

return [

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
        'merge' => [
            'disk'   => env('CHUNKY_MERGE_DISK'),
            'folder' => null,
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
    | Automatically merge chunks
    |--------------------------------------------------------------------------
    |
    | This option defines if chunky should automatically dispatch a merge job
    | once the last chunk has been upload.
    |
    */

    'auto_merge' => env('CHUNKY_AUTO_MERGE', true),

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
        'chunks' => [],

        'merge' => [
            // 'visibility' => 'public'
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation rules
    |--------------------------------------------------------------------------
    |
    | When using a chunk request these rules will be used to validate the input.
    | `index`, `file`, `chunkSize` and `totalSize` attributes are mandatory in
    | order to let chunky work properly, so if missing from the configuration
    | file an exception will be thrown.
    */

    'validation' => [
        // Mandatory
        'index' => [
            'key'   => 'index',
            'rules' => ['required', 'integer', 'min:0'],
        ],
        'file' => [
            'key'   => 'file',
            'rules' => ['required', 'file'],
        ],
        'chunkSize' => [
            'key'   => 'chunkSize',
            'rules' => ['required', 'integer', 'min:1'],
        ],
        'totalSize' => [
            'key'   => 'totalSize',
            'rules' => ['required', 'integer', 'min:1'],
        ],
        // Optional
        'folder' => [
            'key'   => 'folder',
            'rules' => ['filled', 'string'],
        ],
        // --------------------------------------------------------------------------
        // Dropzone chunk uploads example
        // --------------------------------------------------------------------------
        // 'index' => [
        //     'key' => 'dzchunkindex',
        //     'rules' => ['required', 'integer', 'min:0']
        // ],
        // 'file' => [
        //     'key' => 'file',
        //     'rules' => ['required', 'file']
        // ],
        // 'chunkSize' => [
        //     'key' => 'dzchunksize',
        //     'rules' => ['required', 'integer', 'min:1']
        // ],
        // 'totalSize' => [
        //     'key' => 'dztotalfilesize',
        //     'rules' => ['required', 'integer', 'min:1']
        // ],
        // // Optional
        // 'folder' => [
        //     'key' => 'dzuuid',
        //     'rules' => ['required', 'string']
        // ]
        // --------------------------------------------------------------------------
        // Resumable js chunk uploads example
        // --------------------------------------------------------------------------
        // 'index' => [
        //     'key' => 'resumableChunkNumber',
        //     'rules' => ['required', 'integer', 'min:0']
        // ],
        // 'file' => [
        //     'key' => 'file',
        //     'rules' => ['required', 'file']
        // ],
        // 'chunkSize' => [
        //     'key' => 'resumableChunkSize',
        //     'rules' => ['required', 'integer', 'min:1']
        // ],
        // 'totalSize' => [
        //     'key' => 'resumableTotalSize',
        //     'rules' => ['required', 'integer', 'min:1']
        // ],
        // // Optional
        // 'folder' => [
        //     'key' => 'resumableIdentifier',
        //     'rules' => ['required', 'string']
        // ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Chunk json resource
    |--------------------------------------------------------------------------
    |
    | This option defines the class to use when a chunk is transformed to a
    | json resource and returned as response.
    |
    */

    'resource' => \Jobtech\LaravelChunky\Http\Resources\ChunkResource::class,

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
        ],

        'connection' => env('CHUNKY_MERGE_CONNECTION', 'sync'),

        'queue' => env('CHUNKY_MERGE_QUEUE'),
    ],
];
