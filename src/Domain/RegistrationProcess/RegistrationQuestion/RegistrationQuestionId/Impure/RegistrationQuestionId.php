<?php

declare(strict_types=1);

namespace RC\Domain\RegistrationProcess\RegistrationQuestion\RegistrationQuestionId\Impure;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

interface RegistrationQuestionId
{
    public function value(): ImpureValue;
}