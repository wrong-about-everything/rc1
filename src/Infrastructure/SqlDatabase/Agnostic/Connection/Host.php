<?php

declare(strict_types=1);

namespace RC\Infrastructure\SqlDatabase\Agnostic\Connection;

interface Host
{
    public function value(): string;
}
