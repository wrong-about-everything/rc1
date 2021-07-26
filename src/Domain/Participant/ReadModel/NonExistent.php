<?php

declare(strict_types=1);

namespace RC\Domain\Participant\ReadModel;

use Exception;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;

class NonExistent implements Participant
{
    public function value(): ImpureValue
    {
        throw new Exception('This participant does not exist');
    }

    public function exists(): ImpureValue
    {
        return new Successful(new Present(false));
    }
}