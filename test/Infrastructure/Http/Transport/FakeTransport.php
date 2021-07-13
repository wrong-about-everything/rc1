<?php

declare(strict_types=1);

namespace RC\Tests\Infrastructure\Http\Transport;

use RC\Infrastructure\Http\Request\HttpRequest;
use RC\Infrastructure\Http\Transport\HttpTransport;

interface FakeTransport extends HttpTransport
{
    /**
     * @return HttpRequest[]
     */
    public function sentRequests(): array;
}