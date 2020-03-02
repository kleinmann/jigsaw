<?php

namespace TightenCo\Jigsaw\Scaffold;

class CustomInstaller
{
    public $ignore = ['init.php'];
    protected $from;
    protected $builder;
    protected $console;
    protected $question;

    public function setConsole($console): self
    {
        $this->console = $console;

        return $this;
    }

    public function install(ScaffoldBuilder $builder): self
    {
        $this->builder = $builder;

        return $this;
    }

    public function setup(): self
    {
        $this->builder->buildBasicScaffold();

        return $this;
    }

    public function copy($files = null): self
    {
        $this->builder->cacheComposerDotJson();
        $this->builder->copyPresetFiles($files, $this->ignore, $this->from);
        $this->builder->mergeComposerDotJson();

        return $this;
    }

    public function from($from = null): self
    {
        $this->from = $from;

        return $this;
    }

    public function ignore($files): self
    {
        $this->ignore = array_merge($this->ignore, collect($files)->toArray());

        return $this;
    }

    public function delete($files = null): self
    {
        $this->builder->cacheComposerDotJson();
        $this->builder->deleteSiteFiles($files);
        $this->builder->mergeComposerDotJson();

        return $this;
    }

    public function run($commands = null): self
    {
        $this->builder->runCommands($commands);

        return $this;
    }

    public function ask($question, $default = null, $options = null, $errorMessage = null)
    {
        return $this->console->ask($question, $default, $options, $errorMessage);
    }

    public function confirm($question, $default = null, $errorMessage = null)
    {
        return $this->console->confirm($question, $default);
    }

    public function output($text = ''): self
    {
        $this->console->write($text);

        return $this;
    }

    public function info($text = ''): self
    {
        $this->console->info($text);

        return $this;
    }

    public function error($text = ''): self
    {
        $this->console->error($text);

        return $this;
    }

    public function comment($text = ''): self
    {
        $this->console->comment($text);

        return $this;
    }
}
