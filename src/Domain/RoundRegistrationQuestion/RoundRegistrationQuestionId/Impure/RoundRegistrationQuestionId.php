<?php

declare(strict_types=1);

namespace RC\Domain\RoundRegistrationQuestion\RoundRegistrationQuestionId\Impure;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

interface RoundRegistrationQuestionId
{
    public function value(): ImpureValue;
}