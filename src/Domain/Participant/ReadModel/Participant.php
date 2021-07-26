<?php

declare(strict_types=1);

namespace RC\Domain\Participant\ReadModel;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

interface Participant
{
    public function value(): ImpureValue;

    public function exists(): ImpureValue;
}