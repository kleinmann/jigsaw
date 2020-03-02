<?php

namespace TightenCo\Jigsaw\File;

use Illuminate\Filesystem\Filesystem as BaseFilesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class Filesystem extends BaseFilesystem
{
    public function getFile(string $directory, string $filename): SplFileInfo
    {
        $filePath = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

        return new SplFileInfo($filePath, $directory, $filename);
    }

    public function putWithDirectories(string $filePath, string $contents): void
    {
        $directory_path = collect(explode('/', $filePath));
        $directory_path->pop();
        $directory_path = rightTrimPath($directory_path->implode('/'));

        if (! $this->isDirectory($directory_path)) {
            $this->makeDirectory($directory_path, 0755, true);
        }

        $this->put($filePath, $contents);
    }

    public function files(string $directory, array $match = [], array $ignore = [], bool $ignoreDotfiles = false)
    {
        return $directory ? iterator_to_array(
            $this->getFinder($directory, $match, $ignore, $ignoreDotfiles)->files(),
            false
        ) : [];
    }

    public function directories(string $directory, array $match = [], array $ignore = [], bool $ignoreDotfiles = false)
    {
        return $directory ? iterator_to_array(
            $this->getFinder($directory, $match, $ignore, $ignoreDotfiles)->directories(),
            false
        ) : [];
    }

    public function filesAndDirectories(string $directory, array $match = [], array $ignore = [], bool $ignoreDotfiles = false)
    {
        return $directory ? iterator_to_array(
            $this->getFinder($directory, $match, $ignore, $ignoreDotfiles),
            false
        ) : [];
    }

    public function isEmptyDirectory(string $directory): bool
    {
        return $this->exists($directory) ? count($this->files($directory)) === 0 : false;
    }

    protected function getFinder(string $directory, array $match = [], array $ignore = [], bool $ignoreDotfiles = false)
    {
        $finder = Finder::create()
            ->in($directory)
            ->ignoreDotFiles($ignoreDotfiles)
            ->notName('.DS_Store');

        collect($match)->each(function ($pattern) use ($finder) {
            $finder->path($this->getWildcardRegex($pattern));
        });

        collect($ignore)->each(function ($pattern) use ($finder) {
            $finder->notPath($this->getWildcardRegex($pattern));
        });

        return $finder;
    }

    protected function getWildcardRegex(string $pattern): string
    {
        return '#^' . str_replace('\*', '[^/]+', preg_quote(trim($pattern, '/'))) . '($|/)#';
    }
}
