<?php

declare(strict_types=1);

namespace RC\Infrastructure\SqlDatabase\Agnostic\Connection;

interface Credentials
{
    public function username(): string;

    public function password(): string;
}
