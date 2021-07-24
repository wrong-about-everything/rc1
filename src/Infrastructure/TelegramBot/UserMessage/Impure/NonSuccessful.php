<?php

declare(strict_types=1);

namespace RC\Infrastructure\TelegramBot\UserMessage\Impure;

use Exception;
use RC\Infrastructure\ImpureInteractions\ImpureValue;

class NonSuccessful
{
    private $nonSuccessfulImpureValue;

    public function __construct(ImpureValue $nonSuccessfulImpureValue)
    {
        if ($nonSuccessfulImpureValue->isSuccessful()) {
            throw new Exception('You can use only non-successful impure value');
        }

        $this->nonSuccessfulImpureValue = $nonSuccessfulImpureValue;
    }

    public function value(): ImpureValue
    {
        return $this->nonSuccessfulImpureValue;
    }

    public function exists(): ImpureValue
    {
        return $this->nonSuccessfulImpureValue;
    }
}