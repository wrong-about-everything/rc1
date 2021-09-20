<?php

declare(strict_types=1);

namespace RC\Domain\Matches;

use RC\Domain\MeetingRound\MeetingRoundId\Impure\FromMeetingRound;
use RC\Domain\MeetingRound\ReadModel\MeetingRound;
use RC\Domain\MeetingRound\StartDateTime;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;

class ParticipantId2ParticipantsThatHeHasAlreadyMetWith
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
        $response =
            (new Selecting(
                <<<qqqqqqqqqqqqqq
with already_met_users (user_id, match_user_id) as (
    select p.user_id user_id, match.user_id match_user_id
    from meeting_round_pair pair
        join meeting_round_participant p on pair.participant_id = p.id
        join meeting_round_participant match on pair.match_participant_id = match.id
        join meeting_round mr on p.meeting_round_id = mr.id
    where mr.start_date <= ?
    order by mr.start_date desc
)

select mrp.id participant_id, mrp_match.id match_id
from
    (select * from meeting_round_participant where meeting_round_id = ?) mrp
    join already_met_users amu on mrp.user_id = amu.user_id
    join (select * from meeting_round_participant where meeting_round_id = ?) mrp_match on mrp_match.user_id = amu.match_user_id
order by mrp.id, amu.user_id, mrp_match.user_id
qqqqqqqqqqqqqq
                ,
                [
                    (new StartDateTime($this->meetingRound))->value(),
                    (new FromMeetingRound($this->meetingRound))->value()->pure()->raw(),
                    (new FromMeetingRound($this->meetingRound))->value()->pure()->raw(),
                ],
                $this->connection
            ))
                ->response();
        if (!$response->isSuccessful()) {
            return $response;
        }

        return
            new Successful(
                new Present(
                    array_reduce(
                        $response->pure()->raw(),
                        function (array $carry, array $row) {
                            if (!isset($carry[$row['participant_id']])) {
                                $carry[$row['participant_id']] = [];
                            }
                            $carry[$row['participant_id']][] = $row['match_id'];

                            return $carry;
                        },
                        []
                    )
                )
            );
    }
}