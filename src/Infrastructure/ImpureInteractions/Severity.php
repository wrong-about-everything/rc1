<?php

declare(strict_types=1);

namespace RC\Infrastructure\ImpureInteractions;

abstract class Severity
{
    abstract public function value(): int;

    public function equals(Severity $severity): bool
    {
        return $this->value() === $severity->value();
    }
}