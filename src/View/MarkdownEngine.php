<?php

namespace TightenCo\Jigsaw\View;

use Exception;
use Illuminate\Contracts\View\Engine as EngineInterface;
use Symfony\Component\Debug\Exception\FatalThrowableError;
use Throwable;
use TightenCo\Jigsaw\File\Filesystem;
use TightenCo\Jigsaw\Parsers\FrontMatterParser;

class MarkdownEngine implements EngineInterface
{
    /** @var FrontMatterParser */
    private $parser;

    /** @var Filesystem */
    private $file;

    public function __construct(FrontMatterParser $parser, Filesystem $filesystem)
    {
        $this->parser = $parser;
        $this->file = $filesystem;
    }

    /**
     * @param string $path
     */
    public function get($path, array $data = []): string
    {
        return $this->evaluateMarkdown($path);
    }

    protected function evaluateMarkdown(string $path): string
    {
        try {
            $file = $this->file->get($path);

            if ($file) {
                return $this->parser->parseMarkdown($file);
            }
        } catch (Exception $e) {
            $this->handleViewException($e);
        } catch (Throwable $e) {
            $this->handleViewException(new FatalThrowableError($e));
        }
    }

    protected function handleViewException(Exception $e)
    {
        throw $e;
    }
}
