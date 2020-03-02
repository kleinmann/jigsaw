<?php

namespace TightenCo\Jigsaw\Handlers;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use TightenCo\Jigsaw\File\InputFile;
use TightenCo\Jigsaw\File\OutputFile;
use TightenCo\Jigsaw\PageData;

class CollectionItemHandler implements HandlerInterface
{
    /** @var Collection */
    private $config;

    /** @var Collection */
    private $handlers;

    public function __construct(Collection $config, array $handlers)
    {
        $this->config = $config;
        $this->handlers = collect($handlers);
    }

    public function shouldHandle(InputFile $file): bool
    {
        return $this->isInCollectionDirectory($file)
            && ! Str::startsWith($file->getFilename(), ['.', '_']);
    }

    private function isInCollectionDirectory(InputFile $file): bool
    {
        $base = $file->topLevelDirectory();

        return Str::startsWith($base, '_') && $this->hasCollectionNamed($this->getCollectionName($file));
    }

    private function hasCollectionNamed(string $candidate): bool
    {
        return Arr::get($this->config, 'collections.' . $candidate) !== null;
    }

    private function getCollectionName(InputFile $file): string
    {
        return substr($file->topLevelDirectory(), 1);
    }

    public function handle(InputFile $file, PageData $pageData): Collection
    {
        $handler = $this->handlers->first(function ($handler) use ($file) {
            return $handler->shouldHandle($file);
        });
        $pageData->setPageVariableToCollectionItem($this->getCollectionName($file), $file->getFilenameWithoutExtension());

        if ($pageData->page === null) {
            return null;
        }

        return $handler->handleCollectionItem($file, $pageData)
            ->map(function ($outputFile, $templateToExtend) {
                if ($templateToExtend) {
                    $outputFile->data()->setExtending($templateToExtend);
                }

                $path = $outputFile->data()->page->getPath();

                return new OutputFile(
                    dirname($path),
                    basename($path, '.' . $outputFile->extension()),
                    $outputFile->extension(),
                    $outputFile->contents(),
                    $outputFile->data()
                );
            })->values();
    }
}
