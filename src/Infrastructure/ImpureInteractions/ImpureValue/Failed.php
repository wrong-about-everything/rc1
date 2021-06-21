<?php

declare(strict_types=1);

namespace RC\Infrastructure\ImpureInteractions\ImpureValue;

use Exception;
use RC\Infrastructure\ImpureInteractions\Error;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\PureValue;

class Failed implements ImpureValue
{
    private $error;

    public function __construct(Error $error)
    {
        $this->error = $error;
    }

    public function isSuccessful(): bool
    {
        return false;
    }

    public function value(): PureValue
    {
        throw new Exception('Failed impure value does not have a value.');
    }

    public function error(): Error
    {
        return $this->error;
    }
}