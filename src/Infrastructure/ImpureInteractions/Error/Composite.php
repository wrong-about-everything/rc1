<?php

declare(strict_types=1);

namespace RC\Infrastructure\ImpureInteractions\Error;

use RC\Infrastructure\ImpureInteractions\Error;
use RC\Infrastructure\ImpureInteractions\Severity;

class Composite implements Error
{
    private $userMessage;
    private $severity;
    private $logMessage;
    private $context;

    public function __construct(string $userMessage, Severity $severity, string $logMessage, array $context)
    {
        $this->userMessage = $userMessage;
        $this->severity = $severity;
        $this->logMessage = $logMessage;
        $this->context = $context;
    }

    public function userMessage(): string
    {
        return $this->userMessage;
    }

    public function severity(): Severity
    {
        return $this->severity;
    }

    public function logMessage(): string
    {
        return $this->logMessage;
    }

    public function context(): array
    {
        return $this->context;
    }
}