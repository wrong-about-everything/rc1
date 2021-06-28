<?php

declare(strict_types=1);

namespace RC\Infrastructure\UserStory\Response;

use RC\Infrastructure\UserStory\Code\NonRetryableServerError as ServerErrorCode;
use RC\Infrastructure\UserStory\Body;
use RC\Infrastructure\UserStory\Code;
use RC\Infrastructure\UserStory\Response;

class NonRetryableServerError implements Response
{
    private $body;

    public function __construct(Body $body)
    {
        $this->body = $body;
    }

    public function isSuccessful(): bool
    {
        return false;
    }

    public function code(): Code
    {
        return new ServerErrorCode();
    }

    public function headers(): array
    {
        return [];
    }

    public function body(): string
    {
        return $this->body->value();
    }
}