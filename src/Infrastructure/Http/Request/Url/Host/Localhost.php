<?php

declare(strict_types = 1);

namespace RC\Infrastructure\Http\Request\Url\Host;

use RC\Infrastructure\Http\Request\Url\Host;

class Localhost implements Host
{
    public function value(): string
    {
        return 'localhost';
    }
}
