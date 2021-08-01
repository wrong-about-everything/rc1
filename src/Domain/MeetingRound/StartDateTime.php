<?php

declare(strict_types=1);

namespace RC\Domain\MeetingRound;

use Meringue\ISO8601DateTime;
use RC\Domain\MeetingRound\ReadModel\MeetingRound;

class StartDateTime extends ISO8601DateTime
{
    private $meetingRound;

    public function __construct(MeetingRound $meetingRound)
    {
        $this->meetingRound = $meetingRound;
    }

    public function value(): string
    {
        return $this->meetingRound->value()->pure()->raw()['start_date'];
    }
}