<?php

declare(strict_types = 1);

namespace RC\Infrastructure\Http\Request\Url\Query;

use \Exception;
use RC\Infrastructure\Http\Request\Url;
use RC\Infrastructure\Http\Request\Url\Query;

class NonSpecified implements Query
{
    public function value(): string
    {
        throw new Exception('Query is not specified');
    }

    public function isSpecified(): bool
    {
        return false;
    }
}
