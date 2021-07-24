<?php

declare(strict_types=1);

namespace RC\Domain\RoundInvitation\InvitationId\Impure;

use RC\Domain\RoundInvitation\ReadModel\Invitation;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;

class FromInvitation implements InvitationId
{
    private $invitation;

    public function __construct(Invitation $invitation)
    {
        $this->invitation = $invitation;
    }

    public function value(): ImpureValue
    {
        if (!$this->invitation->value()->isSuccessful() || !$this->invitation->value()->pure()->isPresent()) {
            return $this->invitation->value();
        }

        return new Successful(new Present($this->invitation->value()->pure()->raw()['id']));
    }
}