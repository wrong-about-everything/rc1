<?php

declare(strict_types=1);

namespace RC\Domain\Position\Impure;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

class NonSuccessful extends Position
{
    private $nonSuccessfulResult;

    public function __construct(ImpureValue $nonSuccessfulResult)
    {
        $this->nonSuccessfulResult = $nonSuccessfulResult;
    }

    public function value(): ImpureValue
    {
        return $this->nonSuccessfulResult;
    }

    public function exists(): ImpureValue
    {
        return $this->nonSuccessfulResult;
    }
}