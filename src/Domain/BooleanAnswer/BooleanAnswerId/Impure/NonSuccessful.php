<?php

declare(strict_types=1);

namespace RC\Domain\BooleanAnswer\BooleanAnswerId\Impure;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

class NonSuccessful extends BooleanAnswer
{
    private $nonSuccessfulValue;

    public function __construct(ImpureValue $nonSuccessfulValue)
    {
        $this->nonSuccessfulValue = $nonSuccessfulValue;
    }

    public function value(): ImpureValue
    {
        return $this->nonSuccessfulValue;
    }

    public function exists(): ImpureValue
    {
        return $this->nonSuccessfulValue;
    }
}