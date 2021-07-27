<?php

declare(strict_types=1);

namespace RC\Domain\Participant\WriteModel;

use Exception;
use RC\Infrastructure\ImpureInteractions\ImpureValue;

class NonSuccessful implements Participant
{
    private $impureValue;

    public function __construct(ImpureValue $impureValue)
    {
        if ($impureValue->isSuccessful()) {
            throw new Exception('This class is only for non-successful impure values');
        }
        $this->impureValue = $impureValue;
    }

    public function value(): ImpureValue
    {
        return $this->impureValue;
    }
}