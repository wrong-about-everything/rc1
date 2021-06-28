<?php

declare(strict_types=1);

namespace RC\Infrastructure\Logging;

interface Severity
{
    public function value(): string;
}
