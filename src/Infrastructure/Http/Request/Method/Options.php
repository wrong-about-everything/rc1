<?php

declare(strict_types = 1);

namespace RC\Infrastructure\Http\Request\Method;

use RC\Infrastructure\Http\Request\Method;

class Options extends Method
{
    public function value(): string
    {
        return 'OPTIONS';
    }
}