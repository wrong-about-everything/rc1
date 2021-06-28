<?php

declare(strict_types=1);

namespace RC\Infrastructure\Logging\Severity;

use RC\Infrastructure\Logging\Severity;

class Error implements Severity
{
    public function value(): string
    {
        return 'error';
    }
}
