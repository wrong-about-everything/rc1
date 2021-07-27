<?php

declare(strict_types=1);

namespace RC\Domain\Participant\WriteModel;

use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Emptie;

class NonExistent implements Participant
{
    public function value(): ImpureValue
    {
        return new Successful(new Emptie());
    }
}