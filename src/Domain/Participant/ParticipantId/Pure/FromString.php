<?php

declare(strict_types=1);

namespace RC\Domain\Participant\ParticipantId\Pure;

use RC\Infrastructure\Uuid\FromString as UuidFromString;

class FromString implements ParticipantId
{
    private $participantId;

    public function __construct(string $id)
    {
        $this->participantId = new UuidFromString($id);
    }

    public function value(): string
    {
        return $this->participantId->value();
    }

    public function exists(): bool
    {
        return true;
    }
}