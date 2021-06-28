<?php

declare(strict_types=1);

namespace RC\Infrastructure\Logging;

interface LogItem
{
    public function value(): array;
}
