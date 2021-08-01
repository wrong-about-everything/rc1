<?php

declare(strict_types=1);

namespace RC\Domain\MeetingRound\MeetingRoundId\Impure;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

abstract class MeetingRoundId
{
    abstract public function value(): ImpureValue;

    abstract public function exists(): ImpureValue;

    final public function equals(MeetingRoundId $meetingRoundId): bool
    {
        return $this->value()->pure()->raw() === $meetingRoundId->value()->pure()->raw();
    }
}