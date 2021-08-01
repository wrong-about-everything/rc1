<?php

declare(strict_types=1);

namespace RC\Domain\MeetingRound\ReadModel;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

interface MeetingRound
{
    public function value(): ImpureValue;
}