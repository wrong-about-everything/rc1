<?php

declare(strict_types=1);

namespace RC\Activities\Cron\InvitesToTakePartInANewRound;

use RC\Domain\BotId\BotId;
use RC\Infrastructure\Uuid\FromString as Uuid;
use RC\Activities\Cron\InvitesToTakePartInANewRound\Invitation\Logged;
use RC\Activities\Cron\InvitesToTakePartInANewRound\Invitation\Persisted;
use RC\Activities\Cron\InvitesToTakePartInANewRound\Invitation\Sent;
use RC\Domain\RoundInvitation\Status\Pure\Sent as SentStatus;
use RC\Infrastructure\Http\Transport\HttpTransport;
use RC\Infrastructure\Logging\LogItem\InformationMessage;
use RC\Infrastructure\Logging\Logs;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;
use RC\Infrastructure\TelegramBot\BotToken\Pure\FromString;
use RC\Infrastructure\TelegramBot\UserId\Pure\FromInteger;
use RC\Infrastructure\UserStory\Body\Emptie;
use RC\Infrastructure\UserStory\Existent;
use RC\Infrastructure\UserStory\Response;
use RC\Infrastructure\UserStory\Response\Successful;
use RC\Activities\Cron\InvitesToTakePartInANewRound\Invitation\WithPause;

class InvitesToTakePartInANewRound extends Existent
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
        $this->logs->receive(new InformationMessage('Cron invites to attend in a new round scenario started'));

        array_map(
            function (array $meetingRoundInvitation) {
                return
                    (new Logged(
                        $meetingRoundInvitation['name'],
                        new FromInteger($meetingRoundInvitation['telegram_id']),
                        new WithPause(
                            new Persisted(
                                new Uuid($meetingRoundInvitation['id']),
                                new Sent(
                                    new Uuid($meetingRoundInvitation['id']),
                                    new FromInteger($meetingRoundInvitation['telegram_id']),
                                    new FromString($meetingRoundInvitation['token']),
                                    $this->transport
                                ),
                                $this->connection
                            ),
                            100000 // microseconds
                        ),
                        $this->logs
                    ))
                        ->value();
            },
            (new Selecting(
                <<<q
select mri.id, u.telegram_id, b.token, b.name
from meeting_round_invitation mri
    join meeting_round mr on mri.meeting_round_id = mr.id
    join "user" u on mri.user_id = u.id
    join bot b on b.id = mr.bot_id
where mr.bot_id = ? and status != ?
limit 100
q
                ,
                [$this->botId->value(), (new SentStatus())->value()],
                $this->connection
            ))
                ->response()->pure()->raw()
        );

        $this->logs->receive(new InformationMessage('Cron invites to attend in a new round scenario finished'));

        return new Successful(new Emptie());
    }
}