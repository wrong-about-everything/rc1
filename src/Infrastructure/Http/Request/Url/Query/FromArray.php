<?php

declare(strict_types = 1);

namespace RC\Infrastructure\Http\Request\Url\Query;

use RC\Infrastructure\Http\Request\Url\Query;

class FromArray implements Query
{
    private $params;

    public function __construct(array $params)
    {
        $this->params = $params;
    }

    public function value(): string
    {
        return http_build_query($this->params);
    }

    public function isSpecified(): bool
    {
        return true;
    }
}
