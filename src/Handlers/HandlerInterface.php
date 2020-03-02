<?php

namespace TightenCo\Jigsaw\Handlers;

use Illuminate\Support\Collection;
use TightenCo\Jigsaw\File\InputFile;
use TightenCo\Jigsaw\File\OutputFile;
use TightenCo\Jigsaw\PageData;

interface HandlerInterface
{
    public function shouldHandle(InputFile $file): bool;

    /**
     * @return Collection|OutputFile[]
     */
    public function handle(InputFile $file, PageData $pageData): Collection;
}
