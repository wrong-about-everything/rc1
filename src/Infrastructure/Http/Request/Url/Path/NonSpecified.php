<?php

declare(strict_types=1);

namespace RC\Infrastructure\Http\Request\Url\Path;

use RC\Infrastructure\Http\Request\Url\Path;
use Exception;

class NonSpecified implements Path
{
    public function value(): string
    {
        throw new Exception('Path is not specified');
    }

    public function isSpecified(): bool
    {
        return false;
    }
}
