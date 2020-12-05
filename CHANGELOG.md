# Changelog

Every major / minor version release will be documented in the changelog.

## v1.3.0 - 2020-11-2020
Major updates:

* Removed the merge strategy logic. It was completely useless since the package doesn't aim anymore to convert the files after merge.
* Code refactor.
* Temporary files support when merging from remote chunks disks.

Minor fixes:

* Improved tests.
* Updated documentation.
* Fixed Github actions

## v1.2.2 - 2020-09-28
Major features:

* Removed all the logic regarding the mime-type strategy. It was out of context.
* Splitted ChunksManager in two classes ChunksManager and MergeManager
* Code cleanup
* Better handling of remote chunks merge


## v1.1.5 - 2020-09-02
* Little fixes on `Chunk` model. 
* `MergeChunks` job refactor to avoid request serialization. 
* Fixed progress bar while running `deleteAllChunks` in console 
* Other minor fixes.

## v1.1.2 - 2020-09-01

Fixed manager last chunk upload response.

## v1.1.1 - 2020-09-01

Better documentation, fixed issue with remote files mapped into a chunk object.

## v1.0.0 - 2020-08-31

Laravel Chunky first release. Main features:

* Handle chunks upload with custom save disks and folders.
* Handle file merge with custom save disks and folders.
* Different merge strategies based on the file mime type.
* Once the merge is done, the chunks folder is automatically cleared.