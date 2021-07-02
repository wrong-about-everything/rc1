<?php

declare(strict_types=1);

namespace RC\Infrastructure\SqlDatabase\Agnostic\Connection;

interface DatabaseName
{
    public function value(): string;

    public function isSpecified(): bool;
}
