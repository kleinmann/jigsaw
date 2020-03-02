<?php

namespace TightenCo\Jigsaw\Scaffold;

class BasicScaffoldBuilder extends ScaffoldBuilder
{
    public function init(ScaffoldBuilder $preset = null, ScaffoldBuilder $question = null)
    {
        return $this;
    }

    public function build(): self
    {
        $this->scaffoldSite();
        $this->scaffoldMix();

        return $this;
    }

    protected function scaffoldSite(): void
    {
        $this->files->copyDirectory(__DIR__ . '/../../stubs/site', $this->base);
    }

    protected function scaffoldMix(): void
    {
        $this->files->copyDirectory(__DIR__ . '/../../stubs/mix', $this->base);
    }
}
