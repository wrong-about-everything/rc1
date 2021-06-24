<?php

declare(strict_types=1);

namespace RC\Infrastructure\UserStory;

abstract class Header
{
    abstract public function value(): string;

    abstract public function isHttpSpecific(): bool;

    final public function equals(Header $header): bool
    {
        return $this->value() === $header->value();
    }
}