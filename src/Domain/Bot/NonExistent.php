<?php

declare(strict_types=1);

namespace RC\Domain\Bot;

use Exception;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;

class NonExistent implements Bot
{
    public function value(): ImpureValue
    {
        throw new Exception('Bot does not exist');
    }

    public function exists(): ImpureValue
    {
        return new Successful(new Present(false));
    }
}