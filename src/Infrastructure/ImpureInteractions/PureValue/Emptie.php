<?php

declare(strict_types=1);

namespace RC\Infrastructure\ImpureInteractions\PureValue;

use Exception;
use RC\Infrastructure\ImpureInteractions\PureValue;

class Emptie implements PureValue
{
    public function isPresent(): bool
    {
        return false;
    }

    public function raw()
    {
        throw new Exception('Empty value does not have any raw representation.');
    }
}