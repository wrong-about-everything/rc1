<?php

declare(strict_types=1);

namespace RC\Domain\MeetingRound\ReadModel;

use RC\Domain\MeetingRound\MeetingRoundId\Impure\MeetingRoundId;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Emptie;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;

class ById implements MeetingRound
{
    private $meetingRoundId;
    private $connection;
    private $cached;

    public function __construct(MeetingRoundId $meetingRoundId, OpenConnection $connection)
    {
        $this->meetingRoundId = $meetingRoundId;
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
        if (!$this->meetingRoundId->value()->isSuccessful() || $this->meetingRoundId->value()->pure()->raw() === false) {
            return $this->meetingRoundId->value();
        }

        $meetingRound =
            (new Selecting(
                'select * from meeting_round where id = ?',
                [$this->meetingRoundId->value()->pure()->raw()],
                $this->connection
            ))
                ->response();
        if (!$meetingRound->isSuccessful()) {
            return $meetingRound;
        }
        if (!isset($meetingRound->pure()->raw()[0])) {
            return new Successful(new Emptie());
        }

        return new Successful(new Present($meetingRound->pure()->raw()[0]));
    }
}