<?php

declare(strict_types=1);

namespace RC\Infrastructure\ImpureInteractions\Error;

use RC\Infrastructure\ImpureInteractions\Error;
use RC\Infrastructure\ImpureInteractions\Severity;
use RC\Infrastructure\ImpureInteractions\Severity\Alarm;

class AlarmDecline implements Error
{
    private $error;

    public function __construct(string $userMessage, string $logMessage, array $context)
    {
        $this->error = new Composite($userMessage, new Alarm(), $logMessage, $context);
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