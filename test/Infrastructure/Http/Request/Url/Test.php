<?php

declare(strict_types=1);

namespace RC\Tests\Infrastructure\Http\Request\Url;

use RC\Infrastructure\Http\Request\Url;

class Test extends Url
{
    public function value(): string
    {
        return 'http://example.org';
    }
}