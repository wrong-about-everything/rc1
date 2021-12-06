<?php

declare(strict_types=1);

namespace RC\Activities\Cron\InvitesToTakePartInANewRound;

use Meringue\Timeline\Point\Now;
use RC\Domain\Bot\BotId\BotId;
use RC\Domain\Bot\ById;
use RC\Domain\MeetingRound\MeetingRoundId\Impure\FromMeetingRound;
use RC\Domain\MeetingRound\ReadModel\OpenForRegistration;
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
select mri.id, tu.telegram_id
from meeting_round_invitation mri
    join meeting_round mr on mri.meeting_round_id = mr.id
    join telegram_user tu on mri.user_id = tu.id
where mr.id = ? and mri.status in (?)
limit 100
q
                ,
                [
                    (new FromMeetingRound(
                        new OpenForRegistration($this->botId, new Now(), $this->connection)
                    ))
                        ->value()->pure()->raw(),
                    [(new _New())->value(), (new ErrorDuringSending())->value()]
                ],
                $this->connection
            ))
                ->response()->pure()->raw()
        );

        $this->logs->receive(new InformationMessage('Cron invites to attend in a new round scenario finished'));

        return new Successful(new Emptie());
    }
}