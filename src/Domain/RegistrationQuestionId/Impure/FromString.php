<?php

declare(strict_types=1);

namespace RC\Domain\RegistrationQuestionId\Impure;

use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;

class FromString implements RegistrationQuestionId
{
    private $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function value(): ImpureValue
    {
        return new Successful(new Present($this->value));
    }
}