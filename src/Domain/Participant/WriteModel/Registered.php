<?php

declare(strict_types=1);

namespace RC\Domain\Participant\WriteModel;

use Ramsey\Uuid\Uuid;
use RC\Domain\Participant\Status\Pure\Registered as StatusRegistered;
use RC\Domain\RoundInvitation\InvitationId\Impure\InvitationId;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutating;

class Registered implements Participant
{
    private $invitationId;
    private $connection;
    private $cached;

    public function __construct(InvitationId $invitationId, OpenConnection $connection)
    {
        $this->invitationId = $invitationId;
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
        $participantId = Uuid::uuid4()->toString();
        $response =
            (new SingleMutating(
                <<<q
    update meeting_round_participant
    set status = ?
    from meeting_round_invitation mri
    where mri.user_id = meeting_round_participant.user_id and mri.meeting_round_id = meeting_round_participant.meeting_round_id and mri.id = ?
    q
                ,
                [(new StatusRegistered())->value(), $this->invitationId->value()->pure()->raw()],
                $this->connection
            ))
                ->response();
        if (!$response->isSuccessful()) {
            return $response;
        }

        return new Successful(new Present($participantId));
    }
}