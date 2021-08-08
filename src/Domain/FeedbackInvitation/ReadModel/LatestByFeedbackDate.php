<?php

declare(strict_types=1);

namespace RC\Domain\FeedbackInvitation\ReadModel;

use RC\Domain\Bot\BotId\BotId;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Emptie;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;
use RC\Infrastructure\TelegramBot\UserId\Pure\TelegramUserId;

class LatestByFeedbackDate implements FeedbackInvitation
{
    private $telegramUserId;
    private $botId;
    private $connection;
    private $cached;

    public function __construct(TelegramUserId $telegramUserId, BotId $botId, OpenConnection $connection)
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
        $dbResponse =
            (new Selecting(
                <<<q
select fi.*
from feedback_invitation fi
    join meeting_round_participant mrp on fi.participant_id = mrp.id
    join telegram_user tu on tu.id = mrp.user_id
    join meeting_round mr on mr.id = mrp.meeting_round_id
where mr.bot_id = ? and tu.telegram_id = ?
order by mr.invitation_date desc
limit 1
q
                ,
                [$this->botId->value(), $this->telegramUserId->value()],
                $this->connection
            ))
                ->response();
        if (!$dbResponse->isSuccessful()) {
            return $dbResponse;
        }
        if (!isset($dbResponse->pure()->raw()[0])) {
            return new Successful(new Emptie());
        }

        return new Successful(new Present($dbResponse->pure()->raw()[0]));
    }
}