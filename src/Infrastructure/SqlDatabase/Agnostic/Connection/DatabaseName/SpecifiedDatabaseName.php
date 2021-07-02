<?php

declare(strict_types=1);

namespace RC\Infrastructure\SqlDatabase\Agnostic\Connection\DatabaseName;

use Exception;
use RC\Infrastructure\SqlDatabase\Agnostic\Connection\DatabaseName;

class SpecifiedDatabaseName implements DatabaseName
{
    private $value;

    public function __construct(string $value)
    {
        if ($value === '') {
            throw new Exception('Please specify database name explicitly');
        }

        $this->value = $value;
    }

    public function value(): string
    {
        return sprintf('%s', $this->value);
    }

    public function isSpecified(): bool
    {
        return true;
    }
}
