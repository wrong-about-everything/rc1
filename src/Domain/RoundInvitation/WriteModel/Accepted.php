<?php

declare(strict_types=1);

namespace RC\Domain\RoundInvitation\WriteModel;

use RC\Domain\RoundInvitation\InvitationId\Impure\InvitationId;
use RC\Domain\RoundInvitation\Status\Pure\Accepted as AcceptedStatus;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutating;

class Accepted implements Invitation
{
    private $roundInvitationId;
    private $connection;

    public function __construct(InvitationId $roundInvitationId, OpenConnection $connection)
    {
        $this->roundInvitationId = $roundInvitationId;
        $this->connection = $connection;
    }

    public function value(): ImpureValue
    {
        $updatedStatus =
            (new SingleMutating(
                <<<q
update meeting_round_invitation
set status = ?
where id = ?
q
                ,
                [(new AcceptedStatus())->value(), $this->roundInvitationId->value()->pure()->raw()],
                $this->connection
            ))
                ->response();
        if (!$updatedStatus->isSuccessful()) {
            return $updatedStatus;
        }

        return new Successful(new Present($this->roundInvitationId->value()));
    }
}
