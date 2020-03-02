<?php

namespace TightenCo\Jigsaw;

use Illuminate\Support\Collection;
use Symfony\Component\Finder\SplFileInfo;
use TightenCo\Jigsaw\File\InputFile;
use TightenCo\Jigsaw\File\Filesystem;
use TightenCo\Jigsaw\Console\ConsoleOutput;
use TightenCo\Jigsaw\File\OutputFile;
use TightenCo\Jigsaw\Handlers\HandlerInterface;
use TightenCo\Jigsaw\PathResolvers\OutputPathResolverInterface;

class SiteBuilder
{
    /** @var string */
    private $cachePath;

    /** @var Filesystem */
    private $files;

    /** @var HandlerInterface[] */
    private $handlers;

    /** @var OutputPathResolverInterface */
    private $outputPathResolver;

    /** @var ConsoleOutput */
    private $consoleOutput;

    /** @var bool */
    private $useCache = false;

    public function __construct(
        Filesystem $files,
        string $cachePath,
        OutputPathResolverInterface $outputPathResolver,
        ConsoleOutput $consoleOutput,
        $handlers = []
    ) {
        $this->files = $files;
        $this->cachePath = $cachePath;
        $this->outputPathResolver = $outputPathResolver;
        $this->consoleOutput = $consoleOutput;
        $this->handlers = $handlers;
    }

    public function setUseCache(bool $useCache): self
    {
        $this->useCache = $useCache;

        return $this;
    }

    /**
     * @return Collection|OutputFile[]
     */
    public function build(string $source, string $destination, SiteData $siteData): Collection
    {
        $this->prepareDirectory($this->cachePath, ! $this->useCache);
        $generatedFiles = $this->generateFiles($source, $siteData);
        $this->prepareDirectory($destination, true);
        $outputFiles = $this->writeFiles($generatedFiles, $destination);
        $this->cleanup();

        return $outputFiles;
    }

    public function registerHandler(callable $handler): void
    {
        $this->handlers[] = $handler;
    }

    private function prepareDirectories(array $directories): void
    {
        foreach ($directories as $directory) {
            $this->prepareDirectory($directory, true);
        }
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

    private function cleanup(): void
    {
        if (! $this->useCache) {
            $this->files->deleteDirectory($this->cachePath);
        }
    }

    /**
     * @return Collection|OutputFile[]
     */
    private function generateFiles(string $source, SiteData $siteData): Collection
    {
        $files = collect($this->files->files($source));
        $this->consoleOutput->startProgressBar('build', $files->count());

        $files = $files->map(function (SplFileInfo $file) {
            return new InputFile($file);
        })->flatMap(function (InputFile $file) use ($siteData) {
            $this->consoleOutput->progressBar('build')->advance();

            return $this->handle($file, $siteData);
        });

        return $files;
    }

    /**
     * @param Collection|OutputFile[] $files
     *
     * @return Collection|OutputFile[]
     */
    private function writeFiles(Collection $files, string $destination): Collection
    {
        $this->consoleOutput->writeWritingFiles();

        return $files->map(function ($file) use ($destination) {
            return $this->writeFile($file, $destination);
        });
    }

    private function writeFile(OutputFile $file, string $destination)
    {
        $directory = $this->getOutputDirectory($file);
        $this->prepareDirectory("{$destination}/{$directory}");
        $file->putContents("{$destination}/{$this->getOutputPath($file)}");

        return $this->getOutputLink($file);
    }

    /**
     * @param InputFile $file
     * @param SiteData $siteData
     *
     * @return Collection
     */
    private function handle(InputFile $file, SiteData $siteData): Collection
    {
        $meta = $this->getMetaData($file, $siteData->page->baseUrl);

        return $this->getHandler($file)->handle($file, PageData::withPageMetaData($siteData, $meta));
    }

    private function getHandler(InputFile $file): HandlerInterface
    {
        return collect($this->handlers)->first(function (HandlerInterface $handler) use ($file) {
            return $handler->shouldHandle($file);
        });
    }

    private function getMetaData(InputFile $file, string $baseUrl): array
    {
        $filename = $file->getFilenameWithoutExtension();
        $extension = $file->getFullExtension();
        $path = rightTrimPath($this->outputPathResolver->link($file->getRelativePath(), $filename, $file->getExtraBladeExtension() ?: 'html'));
        $relativePath = $file->getRelativePath();
        $url = rightTrimPath($baseUrl) . '/' . trimPath($path);
        $modifiedTime = $file->getLastModifiedTime();

        return compact('filename', 'baseUrl', 'path', 'relativePath', 'extension', 'url', 'modifiedTime');
    }

    private function getOutputDirectory(OutputFile $file): string
    {
        if ($permalink = $this->getFilePermalink($file)) {
            return urldecode(dirname($permalink));
        }

        return urldecode($this->outputPathResolver->directory($file->path(), $file->name(), $file->extension(), $file->page()));
    }

    private function getOutputPath(OutputFile $file): string
    {
        if ($permalink = $this->getFilePermalink($file)) {
            return $permalink;
        }

        return resolvePath(urldecode($this->outputPathResolver->path(
            $file->path(),
            $file->name(),
            $file->extension(),
            $file->page()
        )));
    }

    private function getOutputLink(OutputFile $file): string
    {
        if ($permalink = $this->getFilePermalink($file)) {
            return $permalink;
        }

        return rightTrimPath(urldecode($this->outputPathResolver->link(
            str_replace('\\', '/', $file->path()),
            $file->name(),
            $file->extension(),
            $file->page()
        )));
    }

    private function getFilePermalink(OutputFile $file): ?string
    {
        return $file->data()->page->permalink ? '/' . resolvePath(urldecode($file->data()->page->permalink)) : null;
    }
}
