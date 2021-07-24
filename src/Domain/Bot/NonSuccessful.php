<?php

declare(strict_types=1);

namespace RC\Domain\Bot;

use Exception;
use RC\Infrastructure\ImpureInteractions\ImpureValue;

class NonSuccessful implements Bot
{
    private $impureValue;

    public function __construct(ImpureValue $impureValue)
    {
        if ($impureValue->isSuccessful()) {
            throw new Exception('You can create non-successful bot only from non-successful impure value');
        }

        $this->impureValue = $impureValue;
    }

    public function value(): ImpureValue
    {
        return $this->impureValue;
    }

    public function exists(): ImpureValue
    {
        return $this->impureValue;
    }
}