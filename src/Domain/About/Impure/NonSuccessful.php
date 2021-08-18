<?php

declare(strict_types=1);

namespace RC\Domain\About\Impure;

use Exception;
use RC\Infrastructure\ImpureInteractions\ImpureValue;

class NonSuccessful implements About
{
    private $impureValue;

    public function __construct(ImpureValue $impureValue)
    {
        if ($this->impureValue->isSuccessful()) {
            throw new Exception('This class can be used only with non-successful values');
        }
        $this->impureValue = $impureValue;
    }

    public function value(): ImpureValue
    {
        return $this->impureValue;
    }

    public function empty(): ImpureValue
    {
        return $this->impureValue;
    }

    public function exists(): ImpureValue
    {
        return $this->impureValue;
    }
}
