<?php

namespace TightenCo\Jigsaw\PathResolvers;

interface OutputPathResolverInterface
{
    public function link(string $path, string $name, string $type, int $page = 1): string;

    public function path(string $path, string $name, string $type, int $page = 1): string;

    public function directory(string $path, string $name, string $type, int $page = 1): string;
}
