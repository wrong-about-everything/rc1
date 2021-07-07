<?php

declare(strict_types=1);

namespace RC\Infrastructure\Logging;

interface Logs
{
    public function add(LogItem $item): void;
}
