<?php

declare(strict_types=1);

namespace RC\Domain\RegistrationQuestion\RegistrationQuestionId\Pure;

use RC\Domain\RegistrationQuestion\RegistrationQuestionId\Impure\RegistrationQuestionId as ImpureRegistrationQuestionId;

class FromImpure implements RegistrationQuestionId
{
    private $registrationQuestionId;

    public function __construct(ImpureRegistrationQuestionId $registrationQuestionId)
    {
        $this->registrationQuestionId = $registrationQuestionId;
    }

    public function value(): string
    {
        return $this->registrationQuestionId->value()->pure()->raw();
    }
}