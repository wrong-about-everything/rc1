<?php

declare(strict_types=1);

namespace RC\Domain\RoundRegistrationQuestion;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

interface RoundRegistrationQuestion
{
    public function value(): ImpureValue;
}