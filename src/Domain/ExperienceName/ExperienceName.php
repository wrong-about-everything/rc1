<?php

declare(strict_types=1);

namespace RC\Domain\ExperienceName;

abstract class ExperienceName
{
    abstract public function value(): string;

    abstract public function exists(): bool;

    final public function equals(ExperienceName $ExperienceName): bool
    {
        return $this->value() === $ExperienceName->value();
    }
}