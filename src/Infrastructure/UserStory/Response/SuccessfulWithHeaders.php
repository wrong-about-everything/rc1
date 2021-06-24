<?php

declare(strict_types=1);

namespace RC\Infrastructure\UserStory\Response;

use RC\Infrastructure\UserStory\Body;
use RC\Infrastructure\UserStory\Code;
use RC\Infrastructure\UserStory\Code\Successful as SuccessfulCode;
use RC\Infrastructure\UserStory\Response;

class SuccessfulWithHeaders implements Response
{
    private $body;
    private $headers;

    public function __construct(Body $body, array $headers)
    {
        $this->body = $body;
        $this->headers = $headers;
    }

    public function isSuccessful(): bool
    {
        return true;
    }

    public function code(): Code
    {
        return new SuccessfulCode();
    }

    public function headers(): array
    {
        return $this->headers;
    }

    public function body(): string
    {
        return $this->body->value();
    }
}