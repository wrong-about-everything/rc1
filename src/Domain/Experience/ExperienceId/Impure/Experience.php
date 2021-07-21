<?php

declare(strict_types=1);

namespace RC\Domain\Experience\ExperienceId\Impure;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

abstract class Experience
{
    abstract public function value(): ImpureValue;

    abstract public function exists(): ImpureValue;

    final public function equals(Experience $experience): bool
    {
        return $this->value()->pure()->raw() === $experience->value()->pure()->raw();
    }
}