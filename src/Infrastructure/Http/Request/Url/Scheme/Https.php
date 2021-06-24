<?php

declare(strict_types=1);

namespace RC\Infrastructure\Http\Request\Url\Scheme;

use RC\Infrastructure\Http\Request\Url\Scheme;

class Https implements Scheme
{
    public function value(): string
    {
        return 'https://';
    }

    public function isSpecified(): bool
    {
        return true;
    }
}
