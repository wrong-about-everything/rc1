<?php

declare(strict_types=1);

namespace RC\Domain\RegistrationQuestion\RegistrationQuestionType\Impure;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

abstract class RegistrationQuestionType
{
    abstract public function value(): ImpureValue;

    final public function equals(RegistrationQuestionType $registrationQuestionType): bool
    {
        return $this->value()->pure()->raw() === $registrationQuestionType->value()->pure()->raw();
    }
}