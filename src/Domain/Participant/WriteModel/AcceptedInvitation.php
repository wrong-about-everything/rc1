<?php

declare(strict_types=1);

namespace RC\Domain\Participant\WriteModel;

use Ramsey\Uuid\Uuid;
use RC\Domain\Participant\Status\Pure\RegistrationInProgress;
use RC\Domain\RoundInvitation\InvitationId\Impure\InvitationId;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutating;

class AcceptedInvitation implements Participant
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
    insert into meeting_round_participant (id, user_id, meeting_round_id, status)
    select ?, user_id, meeting_round_id, ?
    from meeting_round_invitation
    where id = ?
    q
                ,
                [$participantId, (new RegistrationInProgress())->value(), $this->invitationId->value()->pure()->raw()],
                $this->connection
            ))
                ->response();
        if (!$response->isSuccessful()) {
            return $response;
        }

        return new Successful(new Present($participantId));
    }
}