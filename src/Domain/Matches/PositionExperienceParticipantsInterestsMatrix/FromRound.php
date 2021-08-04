<?php

declare(strict_types=1);

namespace RC\Domain\Matches\PositionExperienceParticipantsInterestsMatrix;

use RC\Domain\MeetingRound\MeetingRoundId\Impure\FromMeetingRound;
use RC\Domain\MeetingRound\ReadModel\MeetingRound;
use RC\Domain\Participant\Status\Pure\Registered;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;

class FromRound implements PositionsExperiencesParticipantsInterestsMatrix
{
    private $round;
    private $connection;
    private $cached;

    public function __construct(MeetingRound $round, OpenConnection $connection)
    {
        $this->round = $round;
        $this->connection = $connection;
        $this->cached = null;
    }

    public function value(): ImpureValue
    {
        if (is_null($this->cached)) {
            $this->cached = $this->doValue();
        }

        return $this->cached;
    }

    private function doValue(): ImpureValue
    {
        if (!$this->round->value()->isSuccessful()) {
            return $this->round->value();
        }
        if (!$this->round->value()->pure()->isPresent()) {
            return $this->round->value();
        }
        $dataFromDb = $this->dataFromDb();
        if (!$dataFromDb->isSuccessful()) {
            return $dataFromDb;
        }

        return
            new Successful(
                new Present(
                    array_reduce(
                        $dataFromDb->pure()->raw(),
                        function (array $carry, array $currentRow) {
                            if (!isset($carry[$currentRow['position']])) {
                                $carry[$currentRow['position']] = [];
                            }
                            if (!isset($carry[$currentRow['position']][$currentRow['experience']])) {
                                $carry[$currentRow['position']][$currentRow['experience']] = [];
                            }

                            $carry[$currentRow['position']][$currentRow['experience']][$currentRow['participant_id']] = json_decode($currentRow['interested_in']);
                            return $carry;
                        },
                        []
                    )
                )
            );
    }

    private function dataFromDb(): ImpureValue
    {
        return
            (new Selecting(
                <<<q
        select mrp.id as participant_id, mrp.interested_in, bu.position, bu.experience, bu.about
        from meeting_round_participant mrp
            join meeting_round mr on mrp.meeting_round_id = mr.id
            join bot_user bu on bu.user_id = mrp.user_id and bu.bot_id = mr.bot_id
        where mrp.meeting_round_id = ? and mrp.status = ?
        q
                ,
                [(new FromMeetingRound($this->round))->value()->pure()->raw(), (new Registered())->value()],
                $this->connection
            ))
                ->response();
    }
}