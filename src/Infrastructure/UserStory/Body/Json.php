<?php

declare(strict_types=1);

namespace RC\Infrastructure\UserStory\Body;

use RC\Infrastructure\UserStory\Body;

class Json extends Body
{
    private $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function value(): string
    {
        return json_encode($this->payload);
    }

    public function exists(): bool
    {
        return true;
    }
}