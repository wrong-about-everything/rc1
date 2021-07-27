<?php

declare(strict_types=1);

namespace RC\Domain\Participant\ReadModel;

use RC\Domain\RoundInvitation\InvitationId\Impure\InvitationId;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;

class ByInvitationId implements Participant
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
        return $this->concrete()->value();
    }

    public function exists(): ImpureValue
    {
        return $this->concrete()->exists();
    }

    private function concrete()
    {
        if (is_null($this->cached)) {
            $this->cached = $this->doConcrete();
        }

        return $this->cached;
    }

    private function doConcrete(): Participant
    {
        if (!$this->invitationId->value()->isSuccessful()) {
            return new NonSuccessful($this->invitationId->value());
        }
        if (!$this->invitationId->value()->pure()->isPresent()) {
            return new NonExistent();
        }
        $participant =
            (new Selecting(
                <<<q
select mrp.*
from meeting_round_participant mrp
    join meeting_round_invitation mri on mrp.meeting_round_id = mri.meeting_round_id and mrp.user_id = mri.user_id
where mri.id = ?
q
                ,
                [$this->invitationId->value()->pure()->raw()],
                $this->connection
            ))
                ->response();
        if (!$participant->isSuccessful()) {
            return new NonSuccessful($participant);
        }
        if (!isset($participant->pure()->raw()[0])) {
            return new NonExistent();
        }

        return new FromArray($participant->pure()->raw()[0]);
    }
}