<?php

declare(strict_types = 1);

namespace RC\Infrastructure\Http\Request\Url\Query;

use RC\Infrastructure\Http\Request\Url\Query;

class FromString implements Query
{
    private $value;

    public function __construct(string $value)
    {
        // @info: validate (probably with with parse_str)
        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function isSpecified(): bool
    {
        return true;
    }
}
