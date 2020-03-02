<?php

namespace TightenCo\Jigsaw;

class IterableObjectWithDefault extends IterableObject
{
    public function __toString(): string
    {
        return (string) $this->first() ?: '';
    }
}
