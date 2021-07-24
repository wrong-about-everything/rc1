<?php

declare(strict_types=1);

namespace RC\Domain\RoundInvitation\Status\Impure;

use Exception;
use RC\Infrastructure\ImpureInteractions\ImpureValue;

class NonSuccessful extends Status
{
    private $impureValue;

    public function __construct(ImpureValue $nonSuccessfulImpureValue)
    {
        if ($nonSuccessfulImpureValue->isSuccessful()) {
            throw new Exception('Impure value must be non-successful');
        }

        $this->impureValue = $nonSuccessfulImpureValue;
    }

    public function exists(): ImpureValue
    {
        return $this->impureValue;
    }

    public function value(): ImpureValue
    {
        return $this->impureValue;
    }
}