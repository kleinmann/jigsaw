<?php

namespace TightenCo\Jigsaw\CollectionItemHandlers;

use Closure;
use TightenCo\Jigsaw\File\InputFile;
use TightenCo\Jigsaw\Parsers\FrontMatterParser;

class MarkdownCollectionItemHandler
{
    /** @var FrontMatterParser */
    private $parser;

    public function __construct(FrontMatterParser $parser)
    {
        $this->parser = $parser;
    }

    public function shouldHandle(InputFile $file)
    {
        return in_array($file->getExtension(), ['markdown', 'md', 'mdown']);
    }

    public function getItemVariables(InputFile $file): array
    {
        return $this->parser->parse($file->getContents())->frontMatter;
    }

    public function getItemContent($file): Closure
    {
        return function () use ($file) {
            return $this->parser->parseMarkdown($file->getContents());
        };
    }
}
