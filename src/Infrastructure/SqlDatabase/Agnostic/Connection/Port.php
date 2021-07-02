<?php

declare(strict_types=1);

namespace RC\Infrastructure\SqlDatabase\Agnostic\Connection;

interface Port
{
    public function value(): int;

    public function isSpecified(): bool;
}
