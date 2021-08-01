<?php

declare(strict_types=1);

namespace RC\Domain\MeetingRound\MeetingRoundId\Pure;

use RC\Infrastructure\Uuid\UUID;

class FromUuid extends MeetingRoundId
{
    private $concrete;

    public function __construct(UUID $uuid)
    {
        $this->concrete = new FromString($uuid->value());
    }

    public function value(): string
    {
        return $this->concrete->value();
    }

    public function exists(): bool
    {
        return $this->concrete->exists();
    }
}