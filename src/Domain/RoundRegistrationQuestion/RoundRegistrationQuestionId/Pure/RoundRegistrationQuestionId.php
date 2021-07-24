<?php

declare(strict_types=1);

namespace RC\Domain\RoundRegistrationQuestion\RoundRegistrationQuestionId\Pure;

interface RoundRegistrationQuestionId
{
    public function value(): string;
}