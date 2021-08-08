<?php

declare(strict_types=1);

namespace RC\Domain\FeedbackInvitation\FeedbackInvitationId\Pure;

use RC\Infrastructure\Uuid\FromString as Uuid;

class FromString implements FeedbackInvitationId
{
    private $uuid;

    public function __construct(string $uuid)
    {
        $this->uuid = (new Uuid($uuid))->value();
    }

    public function value(): string
    {
        return $this->uuid;
    }
}