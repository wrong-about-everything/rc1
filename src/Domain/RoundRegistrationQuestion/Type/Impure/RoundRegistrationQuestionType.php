<?php

declare(strict_types=1);

namespace RC\Domain\RoundRegistrationQuestion\Type\Impure;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

abstract class RoundRegistrationQuestionType
{
    abstract public function value(): ImpureValue;

    abstract public function exists(): ImpureValue;

    final public function equals(RoundRegistrationQuestionType $questionType): bool
    {
        return $this->value()->pure()->raw() === $questionType->value()->pure()->raw();
    }
}