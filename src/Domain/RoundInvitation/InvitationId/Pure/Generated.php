<?php

declare(strict_types=1);

namespace RC\Domain\RoundInvitation\InvitationId\Pure;

use Ramsey\Uuid\Uuid as RamseyUuid;

class Generated implements InvitationId
{
    private $uuid;

    public function __construct()
    {
        $this->uuid = RamseyUuid::uuid4()->toString();
    }

    public function value(): string
    {
        return $this->uuid;
    }
}