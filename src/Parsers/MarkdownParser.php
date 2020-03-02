<?php

namespace TightenCo\Jigsaw\Parsers;

use Mni\FrontYAML\Markdown\MarkdownParser as FrontYAMLMarkdownParser;

class MarkdownParser implements FrontYAMLMarkdownParser
{
    /** @var JigsawMarkdownParser */
    public $parser;

    public function __construct(JigsawMarkdownParser $parser = null)
    {
        $this->parser = $parser ?: new JigsawMarkdownParser();
    }

    public function __get(string $property)
    {
        return $this->parser->$property;
    }

    public function __set(string $property, $value)
    {
        $this->parser->$property = $value;
    }

    /**
     * @param string $markdown
     *
     * @return string
     */
    public function parse($markdown)
    {
        return $this->parser->parse($markdown);
    }
}
