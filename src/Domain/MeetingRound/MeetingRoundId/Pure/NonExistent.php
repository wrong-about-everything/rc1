<?php

declare(strict_types=1);

namespace RC\Domain\MeetingRound\MeetingRoundId\Pure;

use Exception;

class NonExistent extends MeetingRoundId
{
    public function value(): string
    {
        throw new Exception('This meeting round id does not exist');
    }

    public function exists(): bool
    {
        return false;
    }
}