<?php

declare(strict_types=1);

namespace RC\Domain\Participant\ParticipantId\Pure;

interface ParticipantId
{
    public function value(): string;

    public function exists(): bool;
}