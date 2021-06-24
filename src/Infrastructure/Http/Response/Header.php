<?php

declare(strict_types=1);

namespace RC\Infrastructure\Http\Response;

abstract class Header
{
    abstract public function value(): string;

    abstract public function exists(): bool;

    final public function equals(Header $header): bool
    {
        return $this->value() === $header->value();
    }
}