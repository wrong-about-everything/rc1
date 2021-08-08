<?php

declare(strict_types=1);

namespace RC\Tests\Infrastructure\Http\Transport;

use RC\Infrastructure\Http\Request\Outbound\Request;
use RC\Infrastructure\Http\Transport\HttpTransport;

interface FakeTransport extends HttpTransport
{
    /**
     * @return Request[]
     */
    public function sentRequests(): array;
}