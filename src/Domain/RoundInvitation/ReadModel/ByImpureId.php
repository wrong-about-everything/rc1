<?php

declare(strict_types=1);

namespace RC\Domain\RoundInvitation\ReadModel;

use RC\Domain\RoundInvitation\InvitationId\Impure\InvitationId;
use RC\Domain\RoundInvitation\InvitationId\Pure\FromImpure;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;

class ByImpureId implements Invitation
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
        if (!$this->invitationId->value()->isSuccessful()) {
            return $this->invitationId->value();
        }

        return (new ById(new FromImpure($this->invitationId), $this->connection))->value();
    }
}