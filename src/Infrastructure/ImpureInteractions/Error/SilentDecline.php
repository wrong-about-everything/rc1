<?php

declare(strict_types=1);

namespace RC\Infrastructure\ImpureInteractions\Error;

use RC\Infrastructure\ImpureInteractions\Error;
use RC\Infrastructure\ImpureInteractions\Severity;
use RC\Infrastructure\ImpureInteractions\Severity\Info;

class SilentDecline implements Error
{
    private $error;

    public function __construct(string $userMessage, string $logMessage, array $context)
    {
        $this->error = new Composite($userMessage, new Info(), $logMessage, $context);
    }

    public function userMessage(): string
    {
        return $this->error->userMessage();
    }

    public function severity(): Severity
    {
        return $this->error->severity();
    }

    public function logMessage(): string
    {
        return $this->error->logMessage();
    }

    public function context(): array
    {
        return $this->error->context();
    }
}