<?php

declare(strict_types=1);

namespace RC\Domain\RegistrationProcess\RegistrationQuestion;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

interface RegistrationQuestion
{
    public function value(): ImpureValue;
}