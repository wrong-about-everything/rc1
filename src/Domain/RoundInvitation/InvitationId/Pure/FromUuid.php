<?php

declare(strict_types=1);

namespace RC\Domain\RoundInvitation\InvitationId\Pure;

use RC\Infrastructure\Uuid\UUID;

class FromUuid implements InvitationId
{
    private $uuid;

    public function __construct(UUID $uuid)
    {
        $this->uuid = $uuid;
    }

    public function value(): string
    {
        return $this->uuid->value();
    }
}