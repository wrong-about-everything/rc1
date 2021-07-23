<?php

declare(strict_types=1);

namespace RC\Activities\Cron\InvitesToTakePartInANewRound\Invitation;

use RC\Domain\MeetingRoundInvitation\Status\Pure\Error;
use RC\Domain\MeetingRoundInvitation\Status\Pure\Sent as SentStatus;
use RC\Domain\MeetingRoundInvitation\Status\Pure\Status;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutating;
use RC\Infrastructure\Uuid\UUID;

class Persisted implements Invitation
{
    private $invitationId;
    private $meetingRoundInvitation;
    private $connection;
    private $cached;

    public function __construct(UUID $invitationId, Invitation $meetingRoundInvitation, OpenConnection $connection)
    {
        $this->invitationId = $invitationId;
        $this->meetingRoundInvitation = $meetingRoundInvitation;
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

    private function doValue()
    {
        if (!$this->meetingRoundInvitation->value()->isSuccessful()) {
            $this->updateStatus(new Error());
            return $this->meetingRoundInvitation->value();
        }

        return $this->updateStatus(new SentStatus());
    }

    private function updateStatus(Status $status)
    {
        return
            (new SingleMutating(
                <<<q
update meeting_round_invitation
set status = ?
where id = ?
q
                ,
                [$status->value(), $this->invitationId->value()],
                $this->connection
            ))
                ->response();
    }
}