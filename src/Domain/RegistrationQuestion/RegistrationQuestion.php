<?php

declare(strict_types=1);

namespace RC\Domain\RegistrationQuestion;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

interface RegistrationQuestion
{
    public function value(): ImpureValue;
}