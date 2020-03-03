<?php

namespace TightenCo\Jigsaw\CollectionItemHandlers;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use TightenCo\Jigsaw\File\InputFile;
use TightenCo\Jigsaw\Parsers\FrontMatterParser;

class BladeCollectionItemHandler
{
    /** @var FrontMatterParser */
    private $parser;

    public function __construct(FrontMatterParser $parser)
    {
        $this->parser = $parser;
    }

    public function shouldHandle(InputFile $file)
    {
        return Str::contains($file->getFilename(), '.blade.');
    }

    public function getItemVariables(InputFile $file): array
    {
        $content = $file->getContents();
        $frontMatter = $this->parser->getFrontMatter($content);
        $extendsFromBladeContent = $this->parser->getExtendsFromBladeContent($content);

        return array_merge(
            $frontMatter,
            ['extends' => $extendsFromBladeContent ?: Arr::get($frontMatter, 'extends')]
        );
    }

    public function getItemContent(InputFile $file): void
    {
        return;
    }
}
