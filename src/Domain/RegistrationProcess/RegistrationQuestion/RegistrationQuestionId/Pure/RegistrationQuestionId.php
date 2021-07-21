<?php

declare(strict_types=1);

namespace RC\Domain\RegistrationProcess\RegistrationQuestion\RegistrationQuestionId\Pure;

interface RegistrationQuestionId
{
    public function value(): string;
}