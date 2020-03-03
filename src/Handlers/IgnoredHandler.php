<?php

namespace TightenCo\Jigsaw\Handlers;

use Illuminate\Support\Collection;
use TightenCo\Jigsaw\File\InputFile;
use TightenCo\Jigsaw\PageData;

class IgnoredHandler implements HandlerInterface
{
    public function shouldHandle(InputFile $file): bool
    {
        return preg_match('/(^\/*_)/', $file->getRelativePathname()) === 1;
    }

    public function handle(InputFile $file, PageData $pageData): Collection
    {
        return collect([]);
    }
}
