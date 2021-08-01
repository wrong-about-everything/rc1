<?php

declare(strict_types=1);

namespace RC\Domain\MeetingRound\MeetingRoundId\Pure;

abstract class MeetingRoundId
{
    abstract public function value(): string;

    abstract public function exists(): bool;

    final public function equals(MeetingRoundId $meetingRoundId): bool
    {
        return $this->value() === $meetingRoundId->value();
    }
}