<?php

namespace TightenCo\Jigsaw\Handlers;

use Illuminate\Support\Collection;
use TightenCo\Jigsaw\File\CopyFile;
use TightenCo\Jigsaw\File\InputFile;
use TightenCo\Jigsaw\PageData;

class DefaultHandler implements HandlerInterface
{
    public function shouldHandle(InputFile $file): bool
    {
        return true;
    }

    /**
     * @return Collection|CopyFile[]
     */
    public function handle(InputFile $file, PageData $pageData): Collection
    {
        return collect([
            new CopyFile(
                $file->getPathName(),
                $file->getRelativePath(),
                $file->getBasename('.' . $file->getExtension()),
                $file->getExtension(),
                $pageData
            ),
        ]);
    }
}
