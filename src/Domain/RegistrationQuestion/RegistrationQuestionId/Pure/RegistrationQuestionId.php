<?php

declare(strict_types=1);

namespace RC\Domain\RegistrationQuestion\RegistrationQuestionId\Pure;

interface RegistrationQuestionId
{
    public function value(): string;
}