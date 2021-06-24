<?php

declare(strict_types = 1);

namespace RC\Infrastructure\Http\Request\Method;

use RC\Infrastructure\Http\Request\Method;

class Get extends Method
{
    public function value(): string
    {
        return 'GET';
    }
}