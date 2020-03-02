<?php

namespace TightenCo\Jigsaw\File;

use Illuminate\Support\Str;
use Symfony\Component\Finder\SplFileInfo;

class TemporaryFilesystem
{
    private $tempPath;
    private $filesystem;

    public function __construct(string $tempPath, ?Filesystem $filesystem = null)
    {
        $this->tempPath = $tempPath;
        $this->filesystem = $filesystem ?: new Filesystem();
    }

    public function buildTempPath(string $filename, string $extension): string
    {
        return $this->tempPath . DIRECTORY_SEPARATOR .
            ($filename ? sha1($filename) : Str::random(32)) .
            $extension;
    }

    public function get(string $originalFilename, string $extension): ?InputFile
    {
        $file = new SplFileInfo(
            $this->buildTempPath($originalFilename, $extension),
            $this->tempPath,
            $originalFilename . $extension
        );

        return $file->isReadable() ? new InputFile($file) : null;
    }

    public function put(string $contents, string $filename, string $extension): string
    {
        $path = $this->buildTempPath($filename, $extension);
        $this->filesystem->put($path, $contents);

        return $path;
    }

    public function hasTempDirectory(): bool
    {
        return $this->filesystem->exists($this->tempPath);
    }

    private function delete($path): void
    {
        $this->filesystem->delete($path);
    }
}
