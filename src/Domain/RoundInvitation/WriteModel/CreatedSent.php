<?php

declare(strict_types=1);

namespace RC\Domain\RoundInvitation\WriteModel;

use RC\Domain\MeetingRound\MeetingRoundId\Impure\FromMeetingRound;
use RC\Domain\MeetingRound\ReadModel\MeetingRound;
use RC\Domain\RoundInvitation\InvitationId\Pure\Generated;
use RC\Domain\RoundInvitation\InvitationId\Pure\InvitationId;
use RC\Domain\RoundInvitation\Status\Pure\Sent as SentStatus;
use RC\Domain\TelegramUser\ByTelegramId;
use RC\Domain\TelegramUser\UserId\Impure\FromTelegramUser;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutating;
use RC\Infrastructure\TelegramBot\UserId\Pure\InternalTelegramUserId;

class CreatedSent implements Invitation
{
    private $telegramUserId;
    private $meetingRound;
    private $connection;
    private $cached;

    public function __construct(InternalTelegramUserId $telegramUserId, MeetingRound $meetingRound, OpenConnection $connection)
    {
        $this->telegramUserId = $telegramUserId;
        $this->meetingRound = $meetingRound;
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
        $roundInvitationId = new Generated();
        $response = $this->insert($roundInvitationId);
        if (!$response->isSuccessful()) {
            return $response;
        }

        return new Successful(new Present($roundInvitationId->value()));
    }

    private function insert(InvitationId $invitationId)
    {
        return
            (new SingleMutating(
                <<<q
insert into meeting_round_invitation (id, meeting_round_id, user_id, status)
values (?, ?, ?, ?)
q
                ,
                [
                    $invitationId->value(),
                    (new FromMeetingRound($this->meetingRound))->value()->pure()->raw(),
                    (new FromTelegramUser(
                        new ByTelegramId($this->telegramUserId, $this->connection)
                    ))
                        ->value()->pure()->raw(),
                    (new SentStatus())->value()
                ],
                $this->connection
            ))
                ->response();
    }
}