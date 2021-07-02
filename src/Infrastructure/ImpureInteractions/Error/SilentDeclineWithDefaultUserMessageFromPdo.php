<?php

declare(strict_types=1);

namespace RC\Infrastructure\ImpureInteractions\Error;

use PDO;
use RC\Infrastructure\ImpureInteractions\Error;
use RC\Infrastructure\ImpureInteractions\Severity;
use RC\Infrastructure\ImpureInteractions\Severity\Info;

class SilentDeclineWithDefaultUserMessageFromPdo implements Error
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function userMessage(): string
    {
        return 'Internal server error';
    }

    public function severity(): Severity
    {
        return new Info();
    }

    public function logMessage(): string
    {
        return 'Error from PDO driver';
    }

    public function context(): array
    {
        return $this->pdo->errorInfo();
    }
}