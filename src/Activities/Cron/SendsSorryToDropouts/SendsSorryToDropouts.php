<?php

declare(strict_types=1);

namespace RC\Activities\Cron\SendsSorryToDropouts;

use Meringue\Timeline\Point\Now;
use RC\Domain\Bot\BotId\BotId;
use RC\Domain\Bot\BotToken\Impure\ByBotId;
use RC\Domain\MeetingRound\MeetingRoundId\Impure\FromMeetingRound;
use RC\Domain\MeetingRound\ReadModel\LatestAlreadyStarted;
use RC\Domain\MeetingRound\ReadModel\MeetingRound;
use RC\Domain\Participant\ParticipantId\Pure\FromString;
use RC\Infrastructure\Http\Transport\HttpTransport;
use RC\Infrastructure\Logging\LogItem\ErrorMessage;
use RC\Infrastructure\Logging\LogItem\FromNonSuccessfulImpureValue;
use RC\Infrastructure\Logging\LogItem\InformationMessage;
use RC\Infrastructure\Logging\Logs;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;
use RC\Infrastructure\TelegramBot\UserId\Pure\FromInteger;
use RC\Infrastructure\UserStory\Body\Emptie;
use RC\Infrastructure\UserStory\Existent;
use RC\Infrastructure\UserStory\Response;
use RC\Infrastructure\UserStory\Response\Successful;

// @todo: добавить в крон
class SendsSorryToDropouts extends Existent
{
    private $botId;
    private $transport;
    private $connection;
    private $logs;

    public function __construct(BotId $botId, HttpTransport $transport, OpenConnection $connection, Logs $logs)
    {
        $this->botId = $botId;
        $this->transport = $transport;
        $this->connection = $connection;
        $this->logs = $logs;
    }

    public function response(): Response
    {
        $this->logs->receive(new InformationMessage('Cron sends sorry to dropouts scenario started'));

        $currentRound = new LatestAlreadyStarted($this->botId, new Now(), $this->connection);
        if (!$currentRound->value()->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($currentRound->value()));
            return new Successful(new Emptie());
        }
        if (!$currentRound->value()->pure()->isPresent()) {
            $this->logs->receive(new ErrorMessage('There is no active meeting round. Check both cron and round start datetime in database.'));
            return new Successful(new Emptie());
        }

        array_map(
            function (array $dropout) {
                $participantValue =
                    (new ParticipantNotifiedThatSheIsADropout(
                        new FromString($dropout['dropout_participant_id']),
                        new FromInteger($dropout['dropout_telegram_id']),
                        new ByBotId($this->botId, $this->connection),
                        $this->transport,
                        $this->connection
                    ))
                        ->value();
                if (!$participantValue->isSuccessful()) {
                    $this->logs->receive(new FromNonSuccessfulImpureValue($participantValue));
                }
            },
            $this->dropoutsToNotify($currentRound)
        );

        $this->logs->receive(new InformationMessage('Cron sends sorry to dropouts scenario finished'));

        return new Successful(new Emptie());
    }

    private function dropoutsToNotify(MeetingRound $currentRound)
    {
        return
            (new Selecting(
                <<<q
select
    dropout.dropout_participant_id dropout_participant_id,
    u.telegram_id dropout_telegram_id
from meeting_round_dropout dropout
    join meeting_round_participant p on dropout.dropout_participant_id = p.id
    join telegram_user u on p.user_id = u.id
    join meeting_round mr on mr.id = p.meeting_round_id
    join bot_user bu on bu.user_id = u.id and bu.bot_id = mr.bot_id
where p.meeting_round_id = ? and dropout.sorry_is_sent = false
order by u.telegram_id asc
limit 100
q
                ,
                [(new FromMeetingRound($currentRound))->value()->pure()->raw()],
                $this->connection
            ))
                ->response()->pure()->raw();
    }
}