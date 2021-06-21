<?php

declare(strict_types=1);

namespace RC\Infrastructure\ImpureInteractions;

interface Error
{
    public function userMessage(): string;

    public function severity(): Severity;

    public function logMessage(): string;

    public function context(): array;
}