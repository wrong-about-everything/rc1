<?php

declare(strict_types=1);

namespace RC\Domain\Matches\ReadModel\Impure;

use RC\Domain\MeetingRound\MeetingRoundId\Impure\FromMeetingRound;
use RC\Domain\MeetingRound\ReadModel\MeetingRound;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Emptie;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;

class MatchesForRound implements Matches
{
    private $meetingRound;
    private $connection;

    public function __construct(MeetingRound $meetingRound, OpenConnection $connection)
    {
        $this->meetingRound = $meetingRound;
        $this->connection = $connection;
    }

    public function value(): ImpureValue
    {
        $matches =
            (new Selecting(
                <<<q
select pair.*
from meeting_round_pair pair
    join meeting_round_participant participant on pair.participant_id = participant.id
where participant.meeting_round_id = ?
q
                ,
                [(new FromMeetingRound($this->meetingRound))->value()->pure()->raw()],
                $this->connection
            ))
                ->response();
        if (!$matches->isSuccessful()) {
            return $matches;
        }
        if (empty($matches->pure()->raw())) {
            return new Successful(new Emptie());
        }

        return new Successful(new Present($matches->pure()->raw()));
    }
}