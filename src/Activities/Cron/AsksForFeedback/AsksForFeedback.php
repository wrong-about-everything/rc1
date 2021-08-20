<?php

declare(strict_types=1);

namespace RC\Activities\Cron\AsksForFeedback;

use Meringue\Timeline\Point\Now;
use Ramsey\Uuid\Uuid;
use RC\Domain\Bot\BotId\BotId;
use RC\Domain\Bot\ById;
use RC\Domain\FeedbackInvitation\FeedbackInvitationId\Pure\FromString;
use RC\Domain\FeedbackInvitation\Status\Pure\ErrorDuringSending;
use RC\Domain\FeedbackInvitation\Status\Pure\Generated;
use RC\Domain\FeedbackInvitation\Status\Pure\Sent as SentStatus;
use RC\Domain\FeedbackInvitation\WriteModel\WithPause;
use RC\Domain\MeetingRound\MeetingRoundId\Impure\FromMeetingRound;
use RC\Domain\MeetingRound\ReadModel\ByClosestFeedbackDateTime;
use RC\Domain\MeetingRound\ReadModel\MeetingRound;
use RC\Domain\FeedbackInvitation\WriteModel\Sent;
use RC\Infrastructure\Logging\LogItem\FromNonSuccessfulImpureValue;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutatingQueryWithMultipleValueSets;
use RC\Infrastructure\TelegramBot\UserId\Pure\FromInteger;
use RC\Infrastructure\UserStory\Response\RetryableServerError;
use RC\Infrastructure\Http\Transport\HttpTransport;
use RC\Infrastructure\Logging\LogItem\InformationMessage;
use RC\Infrastructure\Logging\Logs;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;
use RC\Infrastructure\UserStory\Body\Emptie;
use RC\Infrastructure\UserStory\Existent;
use RC\Infrastructure\UserStory\Response;
use RC\Infrastructure\UserStory\Response\Successful;

class AsksForFeedback extends Existent
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
        $this->logs->receive(new InformationMessage('Cron asks for feedback scenario started'));

        $meetingRoundWithLatestFeedbackDate = new ByClosestFeedbackDateTime($this->botId, new Now(), $this->connection);
        if (!$meetingRoundWithLatestFeedbackDate->value()->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($meetingRoundWithLatestFeedbackDate->value()));
            return new RetryableServerError(new Emptie());
        }
        if (empty($this->feedbackInvitationsForRound($meetingRoundWithLatestFeedbackDate))) {
            $response = $this->generateInvitation($meetingRoundWithLatestFeedbackDate);
            if (!$response->isSuccessful()) {
                $this->logs->receive(new FromNonSuccessfulImpureValue($response));
                return new RetryableServerError(new Emptie());
            }
        }

        array_map(
            function (array $invitationRow) {
                (new WithPause(
                    new Sent(
                        new FromString($invitationRow['id']),
                        new FromInteger($invitationRow['telegram_user_id']),
                        new ById($this->botId, $this->connection),
                        $this->transport,
                        $this->connection,
                        $this->logs
                    ),
                    100000
                ))
                    ->value();
            },
            $this->notSentFeedbackInvitationsForRound($meetingRoundWithLatestFeedbackDate)
        );

        $this->logs->receive(new InformationMessage('Cron asks for feedback scenario finished'));

        return new Successful(new Emptie());
    }

    private function feedbackInvitationsForRound(MeetingRound $round)
    {
        return
            (new Selecting(
                <<<q
select fi.id, tu.telegram_id as telegram_user_id
from feedback_invitation fi
    join meeting_round_participant mrp on fi.participant_id = mrp.id
    join telegram_user tu on tu.id = mrp.user_id
where mrp.meeting_round_id = ?
q
                ,
                [(new FromMeetingRound($round))->value()->pure()->raw()],
                $this->connection
            ))
                ->response()->pure()->raw();
    }

    private function notSentFeedbackInvitationsForRound(MeetingRound $round)
    {
        return
            (new Selecting(
                <<<q
select fi.id, tu.telegram_id as telegram_user_id
from feedback_invitation fi
    join meeting_round_participant mrp on fi.participant_id = mrp.id
    join telegram_user tu on tu.id = mrp.user_id
where mrp.meeting_round_id = ? and fi.status in (?)
limit 50
q
                ,
                [(new FromMeetingRound($round))->value()->pure()->raw(), [(new Generated())->value(), (new ErrorDuringSending())->value()]],
                $this->connection
            ))
                ->response()->pure()->raw();
    }

    private function generateInvitation(MeetingRound $meetingRoundWithLatestFeedbackDate)
    {
        return
            (new SingleMutatingQueryWithMultipleValueSets(
                <<<q
insert into feedback_invitation (id, participant_id, status)
values (?, ?, ?)
q
                ,
                array_map(
                    function (array $participantId) use ($meetingRoundWithLatestFeedbackDate) {
                        return [Uuid::uuid4()->toString(), $participantId['id'], (new Generated())->value()];
                    },
                    $this->participantsThatHadAPairInAMeetingRound($meetingRoundWithLatestFeedbackDate)
                ),
                $this->connection
            ))
                ->response();
    }

    private function participantsThatHadAPairInAMeetingRound(MeetingRound $meetingRoundWithLatestFeedbackDate)
    {
        return
            (new Selecting(
                <<<q
select mrp.id
from meeting_round_participant mrp
where mrp.meeting_round_id = ? and exists(select * from meeting_round_pair where participant_id = mrp.id)
q
                ,
                [(new FromMeetingRound($meetingRoundWithLatestFeedbackDate))->value()->pure()->raw()],
                $this->connection
            ))
                ->response()->pure()->raw();
    }
}