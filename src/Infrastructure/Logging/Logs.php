<?php

declare(strict_types=1);

namespace RC\Infrastructure\Logging;

interface Logs
{
    public function receive(LogItem $item): void;

    public function flush(): void;
}
