<?php

declare(strict_types=1);

namespace RC\Domain\MeetingRound\MeetingRoundId\Pure;

use RC\Infrastructure\Uuid\FromString as UuidFromString;

class FromString extends MeetingRoundId
{
    private $meetingRoundId;

    public function __construct(string $meetingRoundId)
    {
        $this->meetingRoundId = (new UuidFromString($meetingRoundId))->value();
    }

    public function value(): string
    {
        return $this->meetingRoundId;
    }

    public function exists(): bool
    {
        return true;
    }
}