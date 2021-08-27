<?php

declare(strict_types=1);

namespace RC\Activities\Cron\InvitesToTakePartInANewRound;

use RC\Domain\Bot\BotId\BotId;
use RC\Domain\Bot\ById;
use RC\Domain\RoundInvitation\InvitationId\Pure\FromUuid;
use RC\Domain\RoundInvitation\Status\Pure\_New;
use RC\Domain\RoundInvitation\Status\Pure\ErrorDuringSending;
use RC\Infrastructure\Uuid\FromString as Uuid;
use RC\Domain\RoundInvitation\WriteModel\Sent;
use RC\Infrastructure\Http\Transport\HttpTransport;
use RC\Infrastructure\Logging\LogItem\InformationMessage;
use RC\Infrastructure\Logging\Logs;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;
use RC\Infrastructure\TelegramBot\UserId\Pure\FromInteger;
use RC\Infrastructure\UserStory\Body\Emptie;
use RC\Infrastructure\UserStory\Existent;
use RC\Infrastructure\UserStory\Response;
use RC\Infrastructure\UserStory\Response\Successful;
use RC\Domain\RoundInvitation\WriteModel\WithPause;

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
                    (new WithPause(
                        new Sent(
                            new FromUuid(new Uuid($meetingRoundInvitation['id'])),
                            new FromInteger($meetingRoundInvitation['telegram_id']),
                            new ById($this->botId, $this->connection),
                            $this->transport,
                            $this->connection,
                            $this->logs
                        ),
                        100000
                    ))
                        ->value();
            },
            (new Selecting(
                <<<q
select mri.id, u.telegram_id, b.token, b.name
from meeting_round_invitation mri
    join meeting_round mr on mri.meeting_round_id = mr.id
    join "telegram_user" u on mri.user_id = u.id
    join bot b on b.id = mr.bot_id
    left join meeting_round_participant mrp on mrp.user_id = u.id and mrp.meeting_round_id = mr.id
where mr.bot_id = ? and mri.status in (?) and mr.invitation_date <= now() + interval '1 minute' and mrp.id is null
limit 100
q
                ,
                [$this->botId->value(), [(new _New())->value(), (new ErrorDuringSending())->value()]],
                $this->connection
            ))
                ->response()->pure()->raw()
        );

        $this->logs->receive(new InformationMessage('Cron invites to attend in a new round scenario finished'));

        return new Successful(new Emptie());
    }
}