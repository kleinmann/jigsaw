<?php

namespace TightenCo\Jigsaw\Loaders;

use Illuminate\Support\Collection;
use TightenCo\Jigsaw\Collection\CollectionRemoteItem;
use TightenCo\Jigsaw\File\Filesystem;

class CollectionRemoteItemLoader
{
    /** @var Filesystem */
    private $files;

    /** @var Collection */
    private $tempDirectories;

    public function __construct(Filesystem $files)
    {
        $this->files = $files;
    }

    public function write($collections, string $source): void
    {
        collect($collections)->each(function ($collection, $collectionName) use ($source) {
            $items = $this->getItems($collection);

            if (collect($items)->count()) {
                $this->writeTempFiles($items, $this->createTempDirectory($source, $collectionName), $collectionName);
            }
        });
    }

    private function createTempDirectory(string $source, string $collectionName): string
    {
        $tempDirectory = $source . '/_' . $collectionName . '/_tmp';
        $this->prepareDirectory($tempDirectory, true);
        $this->tempDirectories[] = $tempDirectory;

        return $tempDirectory;
    }

    public function cleanup(): void
    {
        collect($this->tempDirectories)->each(function ($path) {
            $this->files->deleteDirectory($path);
        });
    }

    private function getItems(Collection $collection)
    {
        if (! $collection->items) {
            return;
        }

        return is_callable($collection->items) ?
            $collection->items->__invoke() :
            $collection->items->toArray();
    }

    private function prepareDirectory(string $directory, bool $clean = false): void
    {
        if (! $this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }

        if ($clean) {
            $this->files->cleanDirectory($directory);
        }
    }

    private function writeTempFiles($items, string $directory, string $collectionName): void
    {
        collect($items)->each(function ($item, $index) use ($directory, $collectionName) {
            $this->writeFile(new CollectionRemoteItem($item, $index, $collectionName), $directory);
        });
    }

    private function writeFile(CollectionRemoteItem $remoteFile, string $directory): void
    {
        $this->files->put($directory . '/' . $remoteFile->getFilename(), $remoteFile->getContent());
    }
}
