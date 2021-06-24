<?php

declare(strict_types = 1);

namespace RC\Infrastructure\Http\Request;

abstract class Url
{
    abstract public function value(): string;

    final public function equals(Url $url): bool
    {
        return $this->value() === $url->value();
    }
}
