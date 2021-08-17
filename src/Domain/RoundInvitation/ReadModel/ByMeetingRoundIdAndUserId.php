<?php

declare(strict_types=1);

namespace RC\Domain\RoundInvitation\ReadModel;

use RC\Domain\MeetingRound\MeetingRoundId\Pure\MeetingRoundId;
use RC\Domain\TelegramUser\UserId\UserId;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Emptie;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;

class ByMeetingRoundIdAndUserId implements Invitation
{
    private $meetingRoundId;
    private $userId;
    private $connection;
    private $cached;

    public function __construct(MeetingRoundId $meetingRoundId, UserId $userId, OpenConnection $connection)
    {
        $this->meetingRoundId = $meetingRoundId;
        $this->userId = $userId;
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
        $response =
            (new Selecting(
                <<<q
select *
from meeting_round_invitation
where meeting_round_id = ? and user_id = ?
q
                ,
                [$this->meetingRoundId->value(), $this->userId->value()],
                $this->connection
            ))
                ->response();
        if (!$response->isSuccessful()) {
            return $response;
        }
        if (!isset($response->pure()->raw()[0])) {
            return new Successful(new Emptie());
        }

        return new Successful(new Present($response->pure()->raw()[0]));
    }
}