<?php

namespace TightenCo\Jigsaw\File;

use Illuminate\View\Compilers\BladeCompiler;

class BladeDirectivesFile
{
    /** @var BladeCompiler */
    protected $bladeCompiler;

    /** @var array */
    protected $directives;

    public function __construct(string $filePath, BladeCompiler $bladeCompiler)
    {
        $this->bladeCompiler = $bladeCompiler;
        $this->directives = file_exists($filePath) ? include $filePath : [];

        if (! is_array($this->directives)) {
            $this->directives = [];
        }
    }

    public function register(): void
    {
        collect($this->directives)->each(function ($callback, $directive) {
            $this->bladeCompiler->directive($directive, $callback);
        });
    }
}
