<?php

declare(strict_types=1);

namespace RC\Domain\Experience\Pure;

abstract class Experience
{
    abstract public function value(): int;

    abstract public function exists(): bool;

    final public function equals(Experience $experience): bool
    {
        return $this->value() === $experience->value();
    }
}