<?php

declare(strict_types=1);

namespace RC\Infrastructure\ImpureInteractions\ImpureValue;

use Exception;
use RC\Infrastructure\ImpureInteractions\Error;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\PureValue;

class Successful implements ImpureValue
{
    private $pureValue;

    public function __construct(PureValue $pureValue)
    {
        $this->pureValue = $pureValue;
    }

    public function isSuccessful(): bool
    {
        return true;
    }

    public function pure(): PureValue
    {
        return $this->pureValue;
    }

    public function error(): Error
    {
        throw new Exception('Successful impure value does not have an error.');
    }
}