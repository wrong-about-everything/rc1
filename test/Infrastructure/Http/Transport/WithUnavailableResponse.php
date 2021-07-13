<?php

declare(strict_types = 1);

namespace RC\Tests\Infrastructure\Http\Transport;

use RC\Infrastructure\Http\Request\Outbound\Request;
use RC\Infrastructure\Http\Response\Inbound\Response;
use RC\Infrastructure\Http\Response\Inbound\Unavailable;

class WithUnavailableResponse implements FakeTransport
{
    private $requests;

    public function __construct()
    {
        $this->requests = [];
    }

    public function response(Request $request): Response
    {
        return new Unavailable();
    }

    /**
     * @return Request[]
     */
    public function sentRequests(): array
    {
        return $this->requests;
    }
}
