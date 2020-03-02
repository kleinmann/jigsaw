<?php

namespace TightenCo\Jigsaw\View;

use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Factory;
use TightenCo\Jigsaw\PageData;

class ViewRenderer
{
    /** @var Factory */
    private $viewFactory;

    /** @var BladeCompiler */
    private $bladeCompiler;

    /** @var array */
    private $extensionEngines = [
        'md' => 'markdown',
        'markdown' => 'markdown',
        'mdown' => 'markdown',
        'blade.md' => 'blade-markdown',
        'blade.mdown' => 'blade-markdown',
        'blade.markdown' => 'blade-markdown',
    ];

    /** @var array */
    private $bladeExtensions = [
        'js', 'json', 'xml', 'rss', 'atom', 'txt', 'text', 'html',
    ];

    public function __construct(Factory $viewFactory, BladeCompiler $bladeCompiler, $config = [])
    {
        $this->config = collect($config);
        $this->viewFactory = $viewFactory;
        $this->bladeCompiler = $bladeCompiler;
        $this->finder = $this->viewFactory->getFinder();
        $this->addExtensions();
        $this->addHintpaths();
    }

    public function getExtension(string $bladeViewPath): string
    {
        return strtolower(pathinfo($this->finder->find($bladeViewPath), PATHINFO_EXTENSION));
    }

    public function render(string $path, PageData $pageData)
    {
        return $this->viewFactory->file($path, $pageData->all())->render();
    }

    public function renderString(string $string): string
    {
        return $this->bladeCompiler->compileString($string);
    }

    private function addHintpaths(): void
    {
        collect($this->config->get('viewHintPaths'))->each(function ($path, $hint) {
            $this->addHintpath($hint, $path);
        });
    }

    private function addHintPath(string $hint, string $path): void
    {
        $this->viewFactory->addNamespace($hint, $path);
    }

    private function addExtensions(): void
    {
        collect($this->extensionEngines)->each(function ($engine, $extension) {
            $this->viewFactory->addExtension($extension, $engine);
        });

        collect($this->bladeExtensions)->each(function ($extension) {
            $this->viewFactory->addExtension($extension, 'php');
            $this->viewFactory->addExtension('blade.' . $extension, 'blade');
        });
    }
}
