<?php

declare(strict_types=1);

namespace RC\Domain\RoundInvitation\ReadModel;

use Meringue\Timeline\Point\Now;
use RC\Domain\Bot\BotId\BotId;
use RC\Domain\MeetingRound\MeetingRoundId\Impure\FromMeetingRound;
use RC\Domain\MeetingRound\ReadModel\ByLatestPassedInvitationDate;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Emptie;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;
use RC\Infrastructure\TelegramBot\UserId\Pure\InternalTelegramUserId;

class InvitationForMeetingRoundWithLatestPassedInvitationDate implements Invitation
{
    private $telegramUserId;
    private $botId;
    private $connection;
    private $cached;

    public function __construct(InternalTelegramUserId $telegramUserId, BotId $botId, OpenConnection $connection)
    {
        $this->telegramUserId = $telegramUserId;
        $this->botId = $botId;
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
        $meetingRoundId = new FromMeetingRound(new ByLatestPassedInvitationDate($this->botId, new Now(), $this->connection));
        if (!$meetingRoundId->exists()->isSuccessful()) {
            return $meetingRoundId->exists();
        }
        if ($meetingRoundId->exists()->pure()->raw() === false) {
            return new Successful(new Emptie());
        }

        $response =
            (new Selecting(
                <<<q
select mri.*
from meeting_round_invitation mri
    join meeting_round mr on mri.meeting_round_id = mr.id
    join "telegram_user" u on mri.user_id = u.id
where u.telegram_id = ? and mr.id = ?
limit 1
q
                ,
                [
                    $this->telegramUserId->value(),
                    $meetingRoundId->value()->pure()->raw(),
                ],
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