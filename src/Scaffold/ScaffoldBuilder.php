<?php

namespace TightenCo\Jigsaw\Scaffold;

use TightenCo\Jigsaw\File\Filesystem;

abstract class ScaffoldBuilder
{
    const IGNORE_DIRECTORIES = [
        'archived',
        'node_modules',
        'vendor',
    ];

    public $base;
    protected $console;
    protected $files;
    protected $process;
    protected $composerCache = [];

    public function __construct(Filesystem $files)
    {
        $this->files = $files;
        $this->setBase();
    }

    abstract public function init(ScaffoldBuilder $preset);

    abstract public function build();

    public function setBase($cwd = null): self
    {
        $this->base = $cwd ?: getcwd();

        return $this;
    }

    public function setConsole($console): self
    {
        $this->console = $console;

        return $this;
    }

    public function archiveExistingSite(): void
    {
        $this->cacheComposerDotJson();
        $this->createEmptyArchive();

        collect($this->allBaseFiles())->each(function ($file) use (&$directories) {
            $source = $file->getPathName();
            $destination = $this->base . DIRECTORY_SEPARATOR . 'archived' . DIRECTORY_SEPARATOR . $file->getRelativePathName();

            if ($this->files->isDirectory($file)) {
                $directories[] = $source;
                $this->files->makeDirectory($destination, 0755, true);
            } else {
                $this->files->move($source, $destination);
            }
        });

        $this->deleteEmptyDirectories($directories);
        $this->restoreComposerDotJson();
    }

    public function deleteExistingSite(): void
    {
        $this->cacheComposerDotJson();

        collect($this->allBaseFiles())->each(function ($file) use (&$directories) {
            $source = $file->getPathName();

            if ($this->files->isDirectory($file)) {
                $directories[] = $source;
            } else {
                $this->files->delete($source);
            }
        });

        $this->deleteEmptyDirectories($directories);
        $this->restoreComposerDotJson();
    }

    public function cacheComposerDotJson(): self
    {
        $this->composerCache = $this->getComposer() ?? [];

        return $this;
    }

    public function restoreComposerDotJson(): void
    {
        $composer = collect($this->composerCache)->only(['require', 'repositories']);

        if ($composer->count() && $jigsaw_require = collect($composer->get('require'))->only('tightenco/jigsaw')) {
            $this->writeComposer($composer->put('require', $jigsaw_require));
        }
    }

    protected function createEmptyArchive(): void
    {
        $archived = $this->base . DIRECTORY_SEPARATOR . 'archived';
        $this->files->deleteDirectory($archived);
        $this->files->makeDirectory($archived, 0755, true);
    }

    protected function deleteEmptyDirectories($directories): void
    {
        collect($directories)->each(function ($directory) {
            if ($this->files->isEmptyDirectory($directory)) {
                $this->files->deleteDirectory($directory);
            }
        });
    }

    protected function allBaseFiles()
    {
        return $this->files->filesAndDirectories(
            $this->base,
            null,
            self::IGNORE_DIRECTORIES,
            $ignore_dotfiles = false
        );
    }

    protected function getComposer()
    {
        $composer = $this->base . DIRECTORY_SEPARATOR . 'composer.json';

        if ($this->files->exists($composer)) {
            return json_decode($this->files->get($composer), true);
        }
    }

    protected function writeComposer($content = null): void
    {
        if ($content) {
            $this->files->put(
                $this->base . DIRECTORY_SEPARATOR . 'composer.json',
                json_encode($content, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
            );
        }
    }
}
