<?php

namespace TightenCo\Jigsaw\Loaders;

use Exception;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Str;
use Symfony\Component\Finder\SplFileInfo;
use TightenCo\Jigsaw\Collection\Collection;
use TightenCo\Jigsaw\Collection\CollectionItem;
use TightenCo\Jigsaw\Console\ConsoleOutput;
use TightenCo\Jigsaw\File\InputFile;
use TightenCo\Jigsaw\Handlers\HandlerInterface;
use TightenCo\Jigsaw\IterableObject;
use TightenCo\Jigsaw\IterableObjectWithDefault;
use TightenCo\Jigsaw\PageVariable;
use TightenCo\Jigsaw\PathResolvers\CollectionPathResolver;
use TightenCo\Jigsaw\SiteData;

class CollectionDataLoader
{
    /** @var Filesystem */
    private $filesystem;

    /** @var ConsoleOutput */
    private $consoleOutput;

    /** @var CollectionPathResolver */
    private $pathResolver;

    /** @var BaseCollection|HandlerInterface[] */
    private $handlers;

    private $source;

    /** @var BaseCollection */
    private $pageSettings;

    /** @var BaseCollection */
    private $collectionSettings;

    public function __construct(Filesystem $filesystem, ConsoleOutput $consoleOutput, CollectionPathResolver $pathResolver, array $handlers = [])
    {
        $this->filesystem = $filesystem;
        $this->pathResolver = $pathResolver;
        $this->handlers = collect($handlers);
        $this->consoleOutput = $consoleOutput;
    }

    public function load(SiteData $siteData, string $source): array
    {
        $this->source = $source;
        $this->pageSettings = $siteData->page;
        $this->collectionSettings = collect($siteData->collections);
        $this->consoleOutput->startProgressBar('collections');

        $collections = $this->collectionSettings->map(function ($collectionSettings, $collectionName) {
            $collection = Collection::withSettings($collectionSettings, $collectionName);
            $collection->loadItems($this->buildCollection($collection));

            return $collection->updateItems($collection->map(function ($item) {
                return $this->addCollectionItemContent($item);
            }));
        });

        return $collections->all();
    }

    private function buildCollection(Collection $collection): BaseCollection
    {
        $path = "{$this->source}/_{$collection->name}";

        if (! $this->filesystem->exists($path)) {
            return collect();
        }

        return collect($this->filesystem->files($path))
            ->reject(function ($file) {
                return Str::startsWith($file->getFilename(), '_');
            })->tap(function ($files) {
                $this->consoleOutput->progressBar('collections')->addSteps($files->count());
            })->map(function ($file) {
                return new InputFile($file);
            })->map(function ($inputFile) use ($collection) {
                $this->consoleOutput->progressBar('collections')->advance();

                return $this->buildCollectionItem($inputFile, $collection);
            });
    }

    private function buildCollectionItem(InputFile $file, BaseCollection $collection)
    {
        $data = $this->pageSettings
            ->merge(['section' => 'content'])
            ->merge($collection->settings)
            ->merge($this->getHandler($file)->getItemVariables($file));
        $data->put('_meta', new IterableObject($this->getMetaData($file, $collection, $data)));
        $path = $this->getPath($data);
        $data->_meta->put('path', $path)->put('url', $this->buildUrls($path));

        return CollectionItem::build($collection, $data);
    }

    private function addCollectionItemContent($item)
    {
        $file = $this->filesystem->getFile($item->getSource(), $item->getFilename() . '.' . $item->getExtension());

        if ($file) {
            $item->setContent($this->getHandler($file)->getItemContent($file));
        }

        return $item;
    }

    private function getHandler(InputFile $file): HandlerInterface
    {
        $handler = $this->handlers->first(function ($handler) use ($file) {
            return $handler->shouldHandle($file);
        });

        if (! $handler) {
            throw new Exception('No matching collection item handler for file: '
                                . $file->getFilenameWithoutExtension() . "." . $file->getExtension() );
        }

        return $handler;
    }

    private function getMetaData(SplFileInfo $file, BaseCollection $collection, BaseCollection $data): array
    {
        $filename = $file->getFilenameWithoutExtension();
        $baseUrl = $data->baseUrl;
        $relativePath = $file->getRelativePath();
        $extension = $file->getFullExtension();
        $collectionName = $collection->name;
        $collection = $collectionName;
        $source = $file->getPath();
        $modifiedTime = $file->getLastModifiedTime();

        return compact('filename', 'baseUrl', 'relativePath', 'extension', 'collection', 'collectionName', 'source', 'modifiedTime');
    }

    private function buildUrls(?IterableObjectWithDefault $paths): ?IterableObjectWithDefault
    {
        $urls = collect($paths)->map(function ($path) {
            return rightTrimPath($this->pageSettings->get('baseUrl')) . '/' . trimPath($path);
        });

        return $urls->count() ? new IterableObjectWithDefault($urls) : null;
    }

    private function getPath($data): ?IterableObjectWithDefault
    {
        $links = $this->pathResolver->link($data->path, new PageVariable($data));

        return $links->count() ? new IterableObjectWithDefault($links) : null;
    }
}
