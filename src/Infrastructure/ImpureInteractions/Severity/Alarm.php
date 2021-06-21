<?php

declare(strict_types=1);

namespace RC\Infrastructure\ImpureInteractions\Severity;

use RC\Infrastructure\ImpureInteractions\Severity;

class Alarm extends Severity
{
    public function value(): int
    {
        return 1;
    }
}